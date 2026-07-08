<?php
function pdf_escape_text(string $text): string {
    $text = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
    return str_replace(["\r", "\n"], ['', ''], $text);
}

function rgb_str(array $rgb): string {
    $r = $rgb[0] / 255.0;
    $g = $rgb[1] / 255.0;
    $b = $rgb[2] / 255.0;
    return sprintf('%.3F %.3F %.3F', $r, $g, $b);
}

function pdf_build_content(array $lines, int $fontSize = 12, int $left = 50, int $top = 760, int $lineHeight = 18): string {
    $pageHeight = 792;
    $headerHeight = 70;
    $primary = [124, 58, 237]; // primary purple from app theme
    $accent = [242, 153, 74]; // amber accent from app theme
    $muted = [237, 233, 254]; // light lavender row background
    $soft = [243, 232, 255];

    $content = '';

    // Draw header background
    $content .= "q\r\n" . rgb_str($primary) . " rg\r\n";
    $content .= "0 " . ($pageHeight - $headerHeight) . " 612 {$headerHeight} re f\r\n";
    $content .= "Q\r\n";

    // Header accent line
    $content .= "q\r\n" . rgb_str($accent) . " rg\r\n";
    $content .= "0 " . ($pageHeight - $headerHeight) . " 612 6 re f\r\n";
    $content .= "Q\r\n";

    // Title text in white inside header
    $title = pdf_escape_text((string)($lines[0] ?? ''));
    $content .= "BT\r\n/F1 18 Tf\r\n50 " . ($pageHeight - 40) . " Td\r\n1 1 1 rg\r\n({$title}) Tj\r\nET\r\n";

    // Table-like rows area (use monospaced font for alignment)
    $startY = $pageHeight - $headerHeight - 20;
    $rowH = $lineHeight;
    $rows = array_slice($lines, 1);

    // Row backgrounds (alternating)
    foreach ($rows as $i => $rline) {
        if ($i % 2 === 1) {
            $y = $startY - ($i * $rowH) - ($rowH - 6);
            $content .= "q\r\n" . rgb_str($muted) . " rg\r\n";
            $content .= "0 {$y} 612 {$rowH} re f\r\n";
            $content .= "Q\r\n";
        }
    }

    // Section subtitle line
    $subtitle = pdf_escape_text((string)($rows[0] ?? ''));
    $content .= "BT\r\n/F1 12 Tf\r\n{$left} " . ($startY + 10) . " Td\r\n0 0 0 rg\r\n({$subtitle}) Tj\r\nET\r\n";

    // Text rows using monospaced layout (replace '|' with column separators)
    $content .= "BT\r\n/F1 {$fontSize} Tf\r\n{$left} " . ($startY - $rowH) . " Td\r\n0 0 0 rg\r\n";
    foreach ($rows as $i => $rline) {
        $rline = (string)$rline;
        if (strpos($rline, '|') !== false) {
            $parts = array_map('trim', explode('|', $rline));
            $pads = [30, 20, 12, 20];
            $cols = [];
            foreach ($parts as $pi => $p) {
                $cols[] = str_pad($p, $pads[$pi] ?? 20);
            }
            $text = implode(' ', $cols);
        } else {
            $text = $rline;
        }
        $content .= "(" . pdf_escape_text($text) . ") Tj\r\n";
        if ($i < count($rows) - 1) {
            $content .= "0 -{$rowH} Td\r\n";
        }
    }
    $content .= "ET\r\n";

    return $content;
}

function pdf_render(array $lines): string {
    $content = pdf_build_content($lines);
    $obj1 = "1 0 obj\r\n<< /Type /Catalog /Pages 2 0 R >>\r\nendobj\r\n";
    $obj2 = "2 0 obj\r\n<< /Type /Pages /Count 1 /Kids [3 0 R] >>\r\nendobj\r\n";
    $obj3 = "3 0 obj\r\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\r\nendobj\r\n";
    $obj4 = "4 0 obj\r\n<< /Length " . strlen($content) . " >>\r\nstream\r\n{$content}endstream\r\nendobj\r\n";
    $obj5 = "5 0 obj\r\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>\r\nendobj\r\n";
    $objects = [$obj1, $obj2, $obj3, $obj4, $obj5];
    $pdf = "%PDF-1.4\r\n";
    $offsets = [];
    foreach ($objects as $object) {
        $offsets[] = strlen($pdf);
        $pdf .= $object;
    }
    $xrefOffset = strlen($pdf);
    $xref = "xref\r\n0 " . (count($objects) + 1) . "\r\n";
    $xref .= "0000000000 65535 f\r\n";
    foreach ($offsets as $offset) {
        $xref .= sprintf("%010d 00000 n\r\n", $offset);
    }
    $pdf .= $xref;
    $pdf .= "trailer\r\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\r\n";
    $pdf .= "startxref\r\n{$xrefOffset}\r\n%%EOF";
    return $pdf;
}

function pdf_send(string $filename, array $lines): void {
    if (headers_sent()) {
        return;
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    header_remove();
    $pdf = pdf_render($lines);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    flush();
    exit;
}
