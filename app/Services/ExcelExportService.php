<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    /**
     * Generate an Excel XML file with multiple sheets.
     *
     * @param  string  $filename  The name of the file
     * @param  array  $sheets  Array of sheets. Each sheet should be an array:
     *                         ['name' => 'Sheet1', 'headings' => ['Col1', 'Col2'], 'rows' => [ ['A1', 'B1'], ['A2', 'B2'] ]]
     * @return StreamedResponse
     */
    public function downloadMultipleSheets($filename, $sheets)
    {
        return response()->streamDownload(function () use ($sheets) {
            $out = fopen('php://output', 'w');

            // XML Header
            fwrite($out, '<?xml version="1.0"?>'."\n");
            fwrite($out, '<?mso-application progid="Excel.Sheet"?>'."\n");
            fwrite($out, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'."\n");
            fwrite($out, ' xmlns:o="urn:schemas-microsoft-com:office:office"'."\n");
            fwrite($out, ' xmlns:x="urn:schemas-microsoft-com:office:excel"'."\n");
            fwrite($out, ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"'."\n");
            fwrite($out, ' xmlns:html="http://www.w3.org/TR/REC-html40">'."\n");

            // Styles
            fwrite($out, ' <Styles>'."\n");
            fwrite($out, '  <Style ss:ID="Default" ss:Name="Normal">'."\n");
            fwrite($out, '   <Alignment ss:Vertical="Bottom"/>'."\n");
            fwrite($out, '   <Borders/>'."\n");
            fwrite($out, '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>'."\n");
            fwrite($out, '   <Interior/>'."\n");
            fwrite($out, '   <NumberFormat/>'."\n");
            fwrite($out, '   <Protection/>'."\n");
            fwrite($out, '  </Style>'."\n");
            fwrite($out, '  <Style ss:ID="s62">'."\n");
            fwrite($out, '   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>'."\n");
            fwrite($out, '   <Interior ss:Color="#F2F2F2" ss:Pattern="Solid"/>'."\n");
            fwrite($out, '  </Style>'."\n");
            fwrite($out, ' </Styles>'."\n");

            // Sheets
            foreach ($sheets as $sheet) {
                $sheetName = htmlspecialchars(substr($sheet['name'], 0, 31)); // Excel limit is 31 chars
                fwrite($out, ' <Worksheet ss:Name="'.$sheetName.'">'."\n");
                fwrite($out, '  <Table>'."\n");

                // Headings
                if (! empty($sheet['headings'])) {
                    fwrite($out, '   <Row>'."\n");
                    foreach ($sheet['headings'] as $heading) {
                        fwrite($out, '    <Cell ss:StyleID="s62"><Data ss:Type="String">'.htmlspecialchars($heading).'</Data></Cell>'."\n");
                    }
                    fwrite($out, '   </Row>'."\n");
                }

                // Rows
                if (! empty($sheet['rows'])) {
                    foreach ($sheet['rows'] as $row) {
                        fwrite($out, '   <Row>'."\n");
                        foreach ($row as $cellValue) {
                            // Empty values
                            if ($cellValue === null || $cellValue === '') {
                                fwrite($out, '    <Cell><Data ss:Type="String"></Data></Cell>'."\n");
                            } else {
                                $type = is_numeric($cellValue) && ! preg_match('/^0\d+/', $cellValue) ? 'Number' : 'String';
                                fwrite($out, '    <Cell><Data ss:Type="'.$type.'">'.htmlspecialchars($cellValue).'</Data></Cell>'."\n");
                            }
                        }
                        fwrite($out, '   </Row>'."\n");
                    }
                }

                fwrite($out, '  </Table>'."\n");
                fwrite($out, ' </Worksheet>'."\n");
            }

            fwrite($out, '</Workbook>');
            fclose($out);
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
