<?php

class PdfGenerator {
    private $page = 1;
    private $content = '';
    private $title;
    private $margin = 50;
    private $pageW = 495;
    private $footerY = 60;
    private $y = 700;
    private $headerDef = [];
    private $widthDef = [];

    public function __construct($title = 'Report') {
        $this->title = $title;
        $this->content = $this->initPdf();
    }

    private function initPdf() {
        $h = '%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Resources<</Font<</F1 4 0 R/F2 5 0 R>>>>/Contents 6 0 R>>endobj
4 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj
5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica-Bold>>endobj
';
        $this->page++;
        $e = $this->escape($this->title);
        $s = "BT /F2 22 Tf {$this->margin} 810 Td ({$e}) Tj ET\n";
        $s .= "BT /F1 9 Tf {$this->margin} 790 Td (Smart Campus Assistant) Tj ET\n";
        return $h . $this->makeObj(6, $s);
    }

    private function makeObj($id, $stream) {
        return "$id 0 obj<</Length " . strlen($stream) . ">>stream\n$stream\nendstream\nendobj\n";
    }

    public function addTitle($text) {
        $s = "BT /F2 14 Tf {$this->margin} {$this->y} Td (" . $this->escape($text) . ") Tj ET\n";
        $this->append($s);
        $this->y -= 20;
    }

    public function addSubtitle($text) {
        $s = "BT /F1 9 Tf {$this->margin} {$this->y} Td (" . $this->escape($text) . ") Tj ET\n";
        $this->append($s);
        $this->y -= 16;
    }

    public function addTableHeader($cells, $widths) {
        if ($this->y < $this->footerY + 40) $this->newPage();
        $this->y -= 4;

        $this->headerDef = $cells;
        $this->widthDef = $widths;

        $y = $this->y;
        $x = $this->margin;
        $totalW = array_sum($widths);
        $h = 16;

        $s = "{$x} {$y} {$totalW} {$h} re S\n";
        $this->append($s);

        $cx = $x;
        foreach ($cells as $i => $cell) {
            $w = $widths[$i] ?? 40;
            $s = "BT /F2 9 Tf " . ($cx + 4) . " " . ($y + 4) . " Td (" . $this->escape($cell) . ") Tj ET\n";
            $this->append($s);
            $cx += $w;
            if ($i < count($cells) - 1) {
                $s = "{$cx} {$y} m {$cx} " . ($y + $h) . " l S\n";
                $this->append($s);
            }
        }

        $this->y = $y - $h;
    }

    public function addRow($cells, $widths) {
        if ($this->y < $this->footerY + 16) {
            $this->newPage();
            if (!empty($this->headerDef)) {
                $this->y -= 4;
                $this->addTableHeader($this->headerDef, $this->widthDef);
            }
        }

        $y = $this->y;
        $x = $this->margin;
        $totalW = array_sum($widths);
        $h = 12;

        $s = "{$x} {$y} {$totalW} {$h} re S\n";
        $this->append($s);

        $cx = $x;
        foreach ($cells as $i => $cell) {
            $w = $widths[$i] ?? 40;
            $s = "BT /F1 8 Tf " . ($cx + 4) . " " . ($y + 3) . " Td (" . $this->escape(substr($cell, 0, 35)) . ") Tj ET\n";
            $this->append($s);
            $cx += $w;
            if ($i < count($cells) - 1) {
                $s = "{$cx} {$y} m {$cx} " . ($y + $h) . " l S\n";
                $this->append($s);
            }
        }

        $this->y = $y - $h;
    }

    private function newPage() {
        $prevCount = $this->page - 1;
        $this->content = str_replace('/Count ' . $prevCount, '/Count ' . $this->page, $this->content);
        $id = 6 + ($prevCount - 1) * 2;
        $newId = $id + 2;
        $e = $this->escape($this->title);
        $s = "BT /F2 22 Tf {$this->margin} 810 Td ({$e}) Tj ET\n";
        $s .= "BT /F1 9 Tf {$this->margin} 790 Td (Smart Campus Assistant) Tj ET\n";
        $this->content .= $this->makeObj($newId, $s);
        $this->page++;
        $this->y = 700;
    }

    private function append($stream) {
        $marker = "endstream\nendobj\n";
        $pos = strrpos($this->content, $marker, -1);
        if ($pos !== false) {
            $before = substr($this->content, 0, $pos);
            $after = substr($this->content, $pos);
            $this->content = $before . $stream . $after;
        }
    }

    private function escape($s) {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        $s = str_replace("\n", '\\n', $s);
        $s = str_replace("\r", '', $s);
        return $s;
    }

    public function output($filename) {
        $this->addFooters();

        $pages = $this->page - 1;
        $kids = '';
        for ($i = 0; $i < $pages; $i++) $kids .= ($i + 3) . ' 0 R ';
        $pagesObj = "2 0 obj<</Type/Pages/Kids[{$kids}]/Count {$pages}>>endobj\n";

        $this->content = preg_replace('/^1 0 obj.*?endobj\n/s', "1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n", $this->content);
        $this->content = preg_replace('/^2 0 obj.*?endobj\n/s', $pagesObj, $this->content);

        preg_match_all('/^(\d+) 0 obj/m', $this->content, $matches, PREG_SET_ORDER);
        $offsets = [];
        foreach ($matches as $m) {
            $pos = strpos($this->content, $m[0]);
            if ($pos !== false) $offsets[(int)$m[1]] = $pos;
        }
        ksort($offsets);

        $xrefOffset = strlen($this->content);
        $count = count($offsets) + 1;
        $xref = "xref\n0 {$count}\n0000000000 65535 f \n";
        foreach ($offsets as $offset) $xref .= sprintf("%010d 00000 n \n", $offset);
        $trailer = "trailer<</Size {$count}/Root 1 0 R>>\nstartxref\n{$xrefOffset}\n%%EOF";

        $body = $this->content . $xref . $trailer;
        header('Content-Type: application/pdf');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Content-Length: ' . strlen($body));
        echo $body;
        exit;
    }

    private function addFooters() {
        $pages = $this->page - 1;
        for ($p = 0; $p < $pages; $p++) {
            $marker = "endstream\nendobj\n";
            $searchPos = 0;
            $count = 0;
            while (($pos = strpos($this->content, $marker, $searchPos)) !== false) {
                $count++;
                if ($count === $p + 1) break;
                $searchPos = $pos + strlen($marker);
            }
            if ($pos === false) continue;

            $f = "{$this->margin} 52 m " . ($this->margin + $this->pageW) . " 52 l S\n";
            $f .= "BT /F1 7 Tf {$this->margin} 42 Td (Generated: " . date('Y-m-d H:i') . ") Tj ET\n";
            $f .= "BT /F1 7 Tf " . ($this->margin + $this->pageW - 24) . " 42 Td (Page {$pages}) Tj ET\n";

            $before = substr($this->content, 0, $pos);
            $after = substr($this->content, $pos);
            $this->content = $before . $f . $after;
        }
    }
}
