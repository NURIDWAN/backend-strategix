<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class WorkflowDiagramService
{
    /**
     * Generate workflow diagram using Kroki.io API
     *
     * @param mixed $workflowDiagram - JSON object with nodes array
     * @return string - Base64 encoded image
     */
    public function generateWorkflowSvg($workflowDiagram)
    {
        if (!$workflowDiagram || !isset($workflowDiagram['nodes'])) {
            return null;
        }

        $nodes = $workflowDiagram['nodes'];
        if (!is_array($nodes) || count($nodes) === 0) {
            return null;
        }

        try {
            // Convert workflow data to Mermaid syntax
            $mermaidSyntax = $this->convertToMermaid($nodes);

            // Generate diagram using Kroki.io API
            $response = Http::timeout(10)->post('https://kroki.io/mermaid/png', [
                'diagram_source' => $mermaidSyntax,
                'diagram_type' => 'mermaid',
                'output_format' => 'png'
            ]);

            if ($response->successful()) {
                $imageData = $response->body();

                // Resize image agar lebih kecil untuk PDF (max width 400px)
                $resizedImage = $this->resizeImage($imageData, 400);

                return 'data:image/png;base64,' . base64_encode($resizedImage);
            } else {
                Log::warning('Kroki.io API failed: ' . $response->status());
                return $this->generateFallbackImage($nodes);
            }
        } catch (\Exception $e) {
            Log::error('Error generating workflow diagram: ' . $e->getMessage());
            return $this->generateFallbackImage($nodes);
        }
    }

    /**
     * Convert nodes to Mermaid flowchart syntax
     */
    private function convertToMermaid($nodes)
    {
        $mermaid = "flowchart TD\n";

        foreach ($nodes as $index => $node) {
            $id = $node['id'] ?? "step_" . ($index + 1);
            $label = $node['label'] ?? 'Step ' . ($index + 1);
            $shape = $node['shape'] ?? 'rect';

            // Format node berdasarkan shape
            $nodeDefinition = $this->getMermaidNodeFormat($id, $label, $shape);
            $mermaid .= "    " . $nodeDefinition . "\n";

            // Add connection to next node
            if ($index < count($nodes) - 1) {
                $nextId = $nodes[$index + 1]['id'] ?? "step_" . ($index + 2);
                $mermaid .= "    {$id} --> {$nextId}\n";
            }
        }

        // Add styling
        $mermaid .= $this->getMermaidStyling($nodes);

        return $mermaid;
    }

    /**
     * Get Mermaid node format based on shape
     */
    private function getMermaidNodeFormat($id, $label, $shape)
    {
        $escapedLabel = str_replace('"', '', $label); // Remove quotes untuk avoid syntax error

        switch ($shape) {
            case 'circle':
                return "{$id}((({$escapedLabel})))"; // Circle/double circle
            case 'diamond':
                return "{$id}{{{$escapedLabel}}}"; // Diamond
            case 'stadium':
                return "{$id}([{$escapedLabel}])"; // Stadium/pill shape
            case 'document':
                return "{$id}[/{$escapedLabel}/]"; // Parallelogram (closest to document)
            default:
                return "{$id}[{$escapedLabel}]"; // Rectangle
        }
    }

    /**
     * Get Mermaid styling based on node types
     */
    private function getMermaidStyling($nodes)
    {
        $styling = "\n";

        foreach ($nodes as $index => $node) {
            $id = $node['id'] ?? "step_" . ($index + 1);
            $type = $node['type'] ?? 'process';
            $color = $this->getNodeColor($type);

            // Convert hex to style
            $styling .= "    style {$id} fill:{$color},stroke:{$color},color:#fff\n";
        }

        return $styling;
    }

    /**
     * Get node color based on type
     */
    private function getNodeColor($type)
    {
        $colors = [
            'start' => '#10B981',
            'end' => '#EF4444',
            'process' => '#3B82F6',
            'decision' => '#F59E0B',
            'preparation' => '#8B5CF6',
            'customer' => '#EC4899',
            'document' => '#6366F1',
        ];

        return $colors[$type] ?? '#6B7280';
    }

    /**
     * Generate fallback simple text-based image if API fails
     */
    private function generateFallbackImage($nodes)
    {
        // Simple SVG fallback dengan list steps
        $height = 40 + (count($nodes) * 30);
        $svg = sprintf(
            '<?xml version="1.0" encoding="UTF-8"?>
            <svg width="300" height="%d" xmlns="http://www.w3.org/2000/svg">
                <rect width="300" height="%d" fill="#f3f4f6"/>
                <text x="150" y="25" text-anchor="middle" font-family="Arial" font-size="14" font-weight="bold">Workflow Diagram</text>',
            $height,
            $height
        );

        $y = 50;
        foreach ($nodes as $index => $node) {
            $label = $node['label'] ?? 'Step ' . ($index + 1);
            $svg .= sprintf(
                '<text x="20" y="%d" font-family="Arial" font-size="12">%d. %s</text>',
                $y,
                $index + 1,
                htmlspecialchars($label)
            );
            $y += 30;
        }

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Resize image to fit in PDF
     */
    private function resizeImage($imageData, $maxWidth)
    {
        try {
            if (extension_loaded('gd')) {
                $source = imagecreatefromstring($imageData);
                if (!$source) {
                    return $imageData; // Return original if can't process
                }

                $originalWidth = imagesx($source);
                $originalHeight = imagesy($source);

                // Only resize if larger than maxWidth
                if ($originalWidth <= $maxWidth) {
                    imagedestroy($source);
                    return $imageData;
                }

                // Calculate new dimensions
                $ratio = $maxWidth / $originalWidth;
                $newWidth = $maxWidth;
                $newHeight = (int)($originalHeight * $ratio);

                // Create resized image
                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency
                imagealphablending($resized, false);
                imagesavealpha($resized, true);

                // Resize
                imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

                // Output to string
                ob_start();
                imagepng($resized, null, 9);
                $output = ob_get_clean();

                // Cleanup
                imagedestroy($source);
                imagedestroy($resized);

                return $output;
            }
        } catch (\Exception $e) {
            Log::warning('Image resize failed: ' . $e->getMessage());
        }

        return $imageData; // Return original if resize fails
    }
}
