<?php
/*
 * FLOCKR
 * Multi-Purpose Church Administration Suite
 * http://github.com/potofcoffee/flockr
 * http://flockr.org
 *
 * Copyright (c) 2016+ Christoph Fischer (chris@toph.de)
 *
 * Parts copyright 2003-2015 Renzo Lauper, renzo@churchtool.org
 * FlockR is a fork from the kOOL project (www.churchtool.org). kOOL is available
 * under the terms of the GNU General Public License (see below).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/************************************************************************************************************************
 *                                                                                                                      *
 * Export-FUNKTIONEN                                                                                                    *
 *                                                                                                                      *
 ************************************************************************************************************************/

/**
 * Creates an XLSX file
 * Based upon PHPExcel (http://phpexcel.codeplex.com/)
 *
 * @param array header: Array holding the header row's cells
 * @param array data: Two dimensional array holding the cell's values
 * @param string filename: Filename two use for the xls file
 * @param string title: Title for the worksheet
 * @param string format: landscape or portrait
 * @param array wrap: Array with column number as key if this column's values should be wrapped
 * @param array formatting: Array containing formatting information
 * @return string the modified filename
 */
function ko_export_to_xlsx(
    $header,
    $data,
    $filename,
    $title = '',
    $format = "landscape",
    $wrap = array(),
    $formatting = array(),
    $linebreak_columns = array()
)
{
    global $ko_path;
    require_once $ko_path . 'inc/phpexcel/PHPExcel.php';
    require_once $ko_path . 'inc/phpexcel/PHPExcel/Writer/Excel2007.php';
    if ($title == '') {
        $title = 'kOOL';
    } else {
        $title = format_userinput($title, 'alphanum');
    }
    $person = ko_get_logged_in_person();
    $xls_default_font = ko_get_setting('xls_default_font');
    $name = $person['vorname'] . ' ' . $person['nachname'];

    $objPHPExcel = new PHPExcel();
    $objPHPExcel->getProperties()->setCreator($name);
    $objPHPExcel->getProperties()->setLastModifiedBy($name);
    $objPHPExcel->getProperties()->setTitle($title);
    $objPHPExcel->getProperties()->setSubject('kOOL-Export');
    $objPHPExcel->getProperties()->setDescription('');


    // Add some data
    $sheet = $objPHPExcel->setActiveSheetIndex(0);
    $sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
    if ($format == 'landscape') {
        $sheet->getPageSetup()->setOrientation(
            PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE
        );
    } else {
        $sheet->getPageSetup()->setOrientation(
            PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT
        );
    }

    if ($xls_default_font) {
        $sheet->getDefaultStyle()->getFont()->setName($xls_default_font);
    } else {
        $sheet->getDefaultStyle()->getFont()->setName('Arial');
    }


    switch (ko_get_setting('xls_title_color')) {
        case 'blue':
            $colorName = PHPExcel_Style_Color::COLOR_BLUE;
            break;
        case 'cyan':
            $colorName = 'FF00FFFF';
            break;
        case 'brown':
            $colorName = 'FFA52A2A';
            break;
        case 'magenta':
            $colorName = 'FFFF00FF';
            break;
        case 'grey':
            $colorName = 'FF808080';
            break;
        case 'green':
            $colorName = PHPExcel_Style_Color::COLOR_GREEN;
            break;
        case 'orange':
            $colorName = 'FFFFA500';
            break;
        case 'purple':
            $colorName = 'FF800080';
            break;
        case 'red':
            $colorName = PHPExcel_Style_Color::COLOR_RED;
            break;
        case 'yellow':
            $colorName = PHPExcel_Style_Color::COLOR_YELLOW;
            break;
        case 'black':
        default:
            $colorName = PHPExcel_Style_Color::COLOR_BLACK;
    }

    $xlsHeaderFormat = array(
        'font' => array(
            'bold' => ko_get_setting('xls_title_bold') ? true : false,
            'color' => array('argb' => $colorName),
            'name' => ko_get_setting('xls_title_font')
        ),
    );

    $xlsTitleFormat = array(
        'font' => array(
            'bold' => ko_get_setting('xls_title_bold') ? true : false,
            'size' => 12,
            'name' => ko_get_setting('xls_title_font')
        )
    );

    $xlsSubtitleFormat = array(
        'font' => array(
            'bold' => ko_get_setting('xls_title_bold') ? true : false,
            'name' => ko_get_setting('xls_title_font')
        )
    );

    $row = 1;
    $col = 0;
    $manual_linebreaks = false;
    //Add header
    if (is_array($header)) {
        if (isset($header['header'])) {
            //Add title
            if ($header['title']) {
                $sheet->getStyleByColumnAndRow(0, $row)->applyFromArray($xlsTitleFormat);
                $sheet->setCellValueByColumnAndRow(0, $row++, $header['title']);
            }
            //Add subtitle
            if (is_array($header['subtitle']) && sizeof($header['subtitle']) > 0) {
                foreach ($header['subtitle'] as $k => $v) {
                    if (substr($k, -1) != ':') {
                        $k .= ':';
                    }
                    $sheet->getStyleByColumnAndRow(0, $row)->applyFromArray($xlsSubtitleFormat);
                    $sheet->setCellValueByColumnAndRow(0, $row, $k);
                    $sheet->setCellValueByColumnAndRow(1, $row++, $v);
                }
            } else {
                if ($header['subtitle']) {
                    $sheet->getStyleByColumnAndRow(0, $row)->applyFromArray($xlsHeaderFormat);
                    $sheet->setCellValueByColumnAndRow(0, $row++, $header['subtitle']);
                }
            }
            $row++;
            //Add column headers
            $col = 0;
            foreach ($header['header'] as $h) {
                $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($xlsHeaderFormat);
                $sheet->setCellValueByColumnAndRow($col++, $row, ko_unhtml($h));
            }
            $row++;
        } else {
            if (is_array($header[0])) {
                foreach ($header as $r) {
                    $col = 0;
                    foreach ($r as $h) {
                        $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($xlsHeaderFormat);
                        $sheet->setCellValueByColumnAndRow($col++, $row, ko_unhtml($h));
                    }
                    $row++;
                }
            } else {
                $manual_linebreaks = true;
                foreach ($header as $h) {
                    $sheet->getStyleByColumnAndRow($col, $row)->applyFromArray($xlsHeaderFormat);
                    $sheet->setCellValueByColumnAndRow($col++, $row, ko_unhtml($h));
                    // add linebreak if the current column is set as a linebreak-column
                    if (in_array($h, $linebreak_columns)) {
                        $row++;
                        $col = 1;
                    }
                }
                $row++;
            }
        }
    }

    //Daten
    foreach ($data as $dd) {
        $col = 0;
        foreach ($dd as $k => $d) {
            if ($wrap[$col] == true) {
                $sheet->getStyleByColumnAndRow($col, $row)->getAlignment()->setWrapText(true);
                $sheet->setCellValueByColumnAndRow($col++, $row, strip_tags(ko_unhtml($d)));
            } else {
                //Set format of cell according to formatting definition
                if (isset($formatting['cells'][($row - 1) . ':' . $col])) {
                    switch ($formatting['cells'][($row - 1) . ':' . $col]) {
                        case 'bold':
                            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
                            break;
                        case 'italic':
                            $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setItalic(true);
                            break;
                    }
                } else {
                    if (isset($formatting['rows'][($row - 1)])) {
                        switch ($formatting['rows'][($row - 1)]) {
                            case 'bold':
                                $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setBold(true);
                                break;
                            case 'italic':
                                $sheet->getStyleByColumnAndRow($col, $row)->getFont()->setItalic(true);
                                break;
                        }
                    } else {
                        $sheet->getStyleByColumnAndRow($col, $row)->getFont()
                            ->setItalic(false)
                            ->setBold(false);
                    }
                }

                $sheet->setCellValueByColumnAndRow($col++, $row, strip_tags(ko_unhtml($d)));
            }
            // set manual linebreak if required
            if ($manual_linebreaks) {
                if (in_array($header[$k], $linebreak_columns)) {
                    $row++;
                    $col = 1;
                }
            }
        }
        $row++;
    }
    // Rename sheet
    $objPHPExcel->getActiveSheet()->setTitle($title);


    // Save Excel file
    $format = 'xlsx';
    if (isset($_SESSION['ses_userid'])) {
        $format = ko_get_userpref($_SESSION['ses_userid'], 'export_table_format');
    }

    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    if ($format == 'xls') {
        $objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
        if (substr($filename, -1) == 'x') {
            $filename = substr($filename, 0, -1);
        }
    }

    $objWriter->save($filename);

    return $filename;
}//ko_export_to_xlsx()


/**
 * Creates an XLS file
 * Based upon php_writeexcel (http://www.bettina-attack.de/jonny/view.php/projects/php_writeexcel)
 *
 * @param array header: Array holding the header row's cells
 * @param array data: Two dimensional array holding the cell's values
 * @param string filename: Filename two use for the xls file
 * @param string title: Title for the worksheet
 * @param string format: landscape or portrait
 * @param array wrap: Array with column number as key if this column's values should be wrapped
 * @param array formatting: Array containing formatting information
 */
function ko_export_to_excel(
    $header,
    $data,
    $filename,
    $title,
    $format = "landscape",
    $wrap = array(),
    $formatting = array()
)
{
    global $ko_path;

    require_once($ko_path . 'inc/class.excelwriter.php');

    $workbook = new writeexcel_workbook($filename);
    $worksheet =& $workbook->addworksheet(substr($title, 0, 30));
    if ($format == "landscape") {
        $worksheet->set_landscape();
    } else {
        $worksheet->set_portrait();
    }

    //set encoding
    //$worksheet->setInputEncoding('ISO-8859-1');

    $col = $row = 0;

    //Define formats
    $xls_default_font = ko_get_setting('xls_default_font');
    $xls_title_font = ko_get_setting('xls_title_font');
    $xls_title_bold = ko_get_setting('xls_title_bold');
    $xls_title_color = ko_get_setting('xls_title_color');

    $format_header =& $workbook->addformat(array(
        'bold' => $xls_title_bold,
        'color' => $xls_title_color,
        'font' => $xls_title_font
    ));
    $format_title =& $workbook->addformat(array('bold' => $xls_title_bold, 'size' => '12', 'font' => $xls_title_font));
    $format_subtitle =& $workbook->addformat(array('bold' => $xls_title_bold, 'font' => $xls_default_font));
    $format_wrap =& $workbook->addformat(array('text_wrap' => 1, 'font' => $xls_default_font));
    $format_default =& $workbook->addformat(array('font' => $xls_default_font));

    //Create formats given in formatting array
    foreach ($formatting['formats'] as $f => $format) {
        ${'f_' . $f} =& $workbook->addformat($format);
    }

    //Add header
    if (is_array($header)) {
        if (isset($header['header'])) {
            //Add title
            if ($header['title']) {
                $worksheet->write($row++, 0, $header['title'], $format_title);
            }
            //Add subtitle
            if (is_array($header['subtitle']) && sizeof($header['subtitle']) > 0) {
                foreach ($header['subtitle'] as $k => $v) {
                    if (substr($k, -1) != ':') {
                        $k .= ':';
                    }
                    $worksheet->write($row, 0, $k, $format_subtitle);
                    $worksheet->write($row++, 1, $v, $format_default);
                }
            } else {
                if ($header['subtitle']) {
                    $worksheet->write($row++, 0, $header['subtitle'], $format_subtitle);
                }
            }
            $row++;
            //Add column headers
            $col = 0;
            foreach ($header['header'] as $h) {
                $worksheet->write($row, $col++, ko_unhtml($h), $format_header);
            }
            $row++;
        } else {
            if (is_array($header[0])) {
                foreach ($header as $r) {
                    $col = 0;
                    foreach ($r as $h) {
                        $worksheet->write($row, $col++, ko_unhtml($h), $format_header);
                    }
                    $row++;
                }
            } else {
                foreach ($header as $h) {
                    $worksheet->write($row, $col++, ko_unhtml($h), $format_header);
                }
                $row++;
            }
        }
    }

    //Daten
    foreach ($data as $dd) {
        $col = 0;
        foreach ($dd as $d) {
            if ($wrap[$col] == true) {
                $worksheet->write($row, $col++, strip_tags(ko_unhtml($d)), $format_wrap);
            } else {
                //Set format of cell according to formatting definition
                if (isset($formatting['cells'][$row . ':' . $col])) {
                    $format =& ${'f_' . $formatting['cells'][$row . ':' . $col]};
                } else {
                    if (isset($formatting['rows'][$row])) {
                        $format =& ${'f_' . $formatting['rows'][$row]};
                    } else {
                        $format =& $format_default;
                    }
                }

                $worksheet->write($row, $col++, strip_tags(ko_unhtml($d)), $format);
            }
        }
        $row++;
    }
    $workbook->close();
    unset($workbook);

}//ko_export_to_excel()


function ko_export_to_csv($header, $data, $filename)
{
    $fp = fopen($filename, 'w');
    fputcsv($fp, $header);
    foreach ($data as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
}//ko_export_to_csv()


function ko_export_to_pdf($layout, $data, $filename)
{
    global $ko_path;

    //PDF starten
    define('FPDF_FONTPATH', $ko_path . 'fpdf/schriften/');
    require($ko_path . 'fpdf/pdf_leute.php');
    $pdf = new PDF_leute($layout["page"]["orientation"], 'mm', 'A4');
    $pdf->Open();
    $pdf->layout = $layout;
    $pdf->SetAutoPageBreak(true, $layout["page"]["margin_bottom"]);

    //Find fonts actually used in this document
    $used_fonts = array();
    foreach (array("header", "footer") as $i) {
        foreach (array("left", "center", "right") as $j) {
            $used_fonts[] = $layout[$i][$j]["font"];
        }
    }
    $used_fonts[] = $layout["headerrow"]["font"];
    $used_fonts[] = $layout["col_template"]["_default"]["font"];
    $used_fonts = array_unique($used_fonts);
    //Add fonts
    $fonts = ko_get_pdf_fonts();
    foreach ($fonts as $font) {
        if (!in_array($font["id"], $used_fonts)) {
            continue;
        }
        $pdf->AddFont($font["id"], '', $font["file"]);
    }

    //Set borders from layout (if defined)
    if (array_key_exists('borders', $layout)) {
        $pdf->border($layout['borders']);
    } else {
        $pdf->border(true);
    }
    if (array_key_exists('cellBorders', $layout)) {
        $pdf->SetCellBorders(strtoupper($layout['cellBorders']));
    }

    $pdf->SetMargins($layout["page"]["margin_left"], $layout["page"]["margin_top"], $layout["page"]["margin_right"]);

    //Prepare replacement-array for header and footer
    $map["[[Day]]"] = strftime("%d", time());
    $map["[[Month]]"] = strftime("%m", time());
    $map["[[MonthName]]"] = strftime("%B", time());
    $map["[[Year]]"] = strftime("%Y", time());
    $map["[[Hour]]"] = strftime("%H", time());
    $map["[[Minute]]"] = strftime("%M", time());
    $map["[[kOOL-URL]]"] = $BASE_URL;
    $pdf->header_map = $map;


    for ($i = 0; $i < 2; $i++) {

        //First loop: Gather string widths for whole table
        if ($i == 0) {
            $find_widths = true;

            //Add header titles
            $string_widths = array();
            $colcounter = 0;
            $pdf->SetFont($pdf->layout["headerrow"]["font"], "", $pdf->layout["headerrow"]["fontsize"]);
            foreach ($pdf->layout["columns"] as $colName) {
                $string_widths[$colcounter][] = $pdf->getStringWidth($colName);
                $headerwidth[$colcounter] = $pdf->getStringWidth($colName);
                $colcounter++;
            }
        } //Second loop: Use string widths to calculate columns widths for table
        else {
            //Calculate column widths for all columns
            foreach ($string_widths as $col => $values) {
                $num = $sum = $max = 0;
                foreach ($values as $value) {
                    if ($value == 0) {
                        continue;
                    }
                    $sum += $value;
                    $num++;
                    $max = max($max, $value);
                }
                $averages[$col] = $sum / $num;
                $maxs[$col] = $max;
            }

            //Find total width of full text
            $page_width = $pdf->w - $layout["page"]["margin_left"] - $layout["page"]["margin_right"];
            $maxwidth = $page_width / 3;
            //Don't let a single column use more than a third of the page width
            foreach ($averages as $col => $width) {
                if ($width > $maxwidth) {
                    $averages[$col] = $maxwidth;
                }
                $maxs[$col] = min($maxs[$col], $maxwidth);
            }
            //Keep a minimum column width of 10mm
            $minwidth = 10;
            foreach ($averages as $col => $width) {
                if ($width < $minwidth) {
                    $averages[$col] = $minwidth;
                }
            }

            $total_width = 0;
            foreach ($averages as $col => $width) {
                $total_width += $width;
            }

            //Use space to enlarge the columns where the header is wider than the column
            if ($total_width < $page_width) {
                $total_need = 0;
                $need = array();
                //Find needs for all columns
                foreach ($averages as $col => $width) {
                    if ($width < $headerwidth[$col]) {
                        $need[$col] = $headerwidth[$col] - $width;
                        $total_need += $need[$col];
                    }
                }
                $need_factor = ($page_width - $total_width) / $total_need;
                foreach ($averages as $col => $value) {
                    if ($need[$col]) {
                        //Only grow the row to the width of the headertext
                        $new_max = $value + $need_factor * $need[$col];
                        $averages[$col] = min($headerwidth[$col], $new_max);
                    }
                }
            }

            //Use space to enlarge the columns where the content is wider than the column width
            if ($total_width < $page_width) {
                $total_need = 0;
                $need = array();
                //Find needs for all columns
                foreach ($averages as $col => $width) {
                    if ($width < $maxs[$col]) {
                        $need[$col] = $maxs[$col] - $width;
                        $total_need += $need[$col];
                    }
                }
                $need_factor = ($page_width - $total_width) / $total_need;
                foreach ($averages as $col => $value) {
                    if ($need[$col]) {
                        //Only grow the row to the width of the headertext
                        $new_max = $value + $need_factor * $need[$col];
                        $averages[$col] = min($maxs[$col], $new_max);
                    }
                }
            }

            $total_width = 0;
            foreach ($averages as $col => $width) {
                $total_width += $width;
            }

            //Get scaling factor
            $factor = $page_width / $total_width;

            //Calculate single widths for all columns
            $widths = array();
            foreach ($averages as $value) {
                $widths[] = $factor * $value;
            }
            $pdf->SetWidths($widths);

            $pdf->AddPage();
            $find_widths = false;
        }


        //Loop all addresses
        foreach ($data as $row) {
            //Layout for normal content
            $pdf->SetFont($layout["col_template"]["_default"]["font"], "",
                $layout["col_template"]["_default"]["fontsize"]);


            if ($find_widths) {
                //Store width for width calculation
                foreach ($row as $key => $value) {
                    $string_widths[$key][] = $pdf->getStringWidth($value);
                }
            } else {
                //Save this row in pdf
                $pdf->SetZeilenhoehe($layout["col_template"]["_default"]["fontsize"] / 2);
                if (is_array($layout['col_template']['_default']['aligns'])) {
                    $pdf->SetAligns($layout['col_template']['_default']['aligns']);
                }
                $pdf->Row($row);
            }
        }//foreach(data)

    }//for(i=0..2)

    $pdf->Output($filename);

}//ko_export_to_pdf()


/**
 * Merges several PDF files into one. Uses shell command gs
 *
 * @param $files array holding the files
 * @param $output string Filename used for output
 */
function ko_merge_pdf_files($files, $output)
{
    $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$output " . implode(' ', $files);
    $result = shell_exec($cmd);
}//ko_merge_pdf_files()


/**
 * @param FPDF $pdf a FPDF object, that can calculate the width of a string
 * @param $width         the maximum width of the resulting string (in size $size)
 * @param $text          the text that should fit in the supplied $width             -> REF
 * @param $time          a time that should fit in the supplied $width               -> REF
 * @param $size          the maximum fontsize (in pt)                                -> REF
 * @param int $minSize the minimum fontsize (in pt)
 * @param int $mode mode 0:
 *                            handles time and text, $size is resuced until $time fits, then $text is shortened
 *                            till it fits
 *                       mode 1:
 *                            handles only text, is shortened until it fits $width, $size is not modified
 * @return bool          returns false if the supplied $time can't fit into the supplied $width
 */
function ko_get_fitting_text_width(FPDF $pdf, $width, &$text, &$time, &$size, $minSize = 6, $mode = 0)
{
    $tempSize = $pdf->FontSize;
    $pdf->SetFontSize($size);
    if ($pdf->GetStringWidth($text) > $width || $pdf->GetStringWidth($time) > $width) {

        if ($mode == 0) {
            // shorten $time if possible
            $newTime = (substr($time, 0, 1) == '0' ? substr($time, 1) : $time);
            $shortTime = ($newTime != $time);
            $looping = true;

            // reduce $size until $time (shortened, if possible) fits into $width
            while ($pdf->GetStringWidth($time) > $width && $looping && $size >= $minSize) {
                if ($shortTime && $pdf->GetStringWidth($newTime) <= $width) {
                    $time = $newTime;
                    $looping = false;
                } else {
                    $pdf->SetFontSize(--$size);
                }
            }
        }

        // shorten $text to make it fit into $width
        $repr = $text;
        while ($pdf->GetStringWidth($text) > $width && $text != '..') {
            if (substr($repr, strlen($repr) - 4, strlen($repr)) == '@..@') {
                $rTemp = substr($repr, 0, strlen($repr) - 5);
                $tTemp = substr($text, 0, strlen($text) - 3);

                $text = $tTemp . '..';
                $repr = $rTemp . '@..@';
            } else {
                $rTemp = substr($repr, 0, strlen($repr) - 1);
                $tTemp = substr($text, 0, strlen($text) - 1);

                $text = $tTemp . '..';
                $repr = $rTemp . '@..@';
            }
        }
        if ($text == '..') {
            $text = '';
        }
    }

    // restore fontsize
    $pdf->SetFontSize($tempSize);
    if ($size < $minSize) {
        return false;
    } else {
        return true;
    }
}

function ko_has_time_format($t)
{
    $pattern = '/^([0-1][0-9]|2[0-4]):[0-5][0-9]$/';
    return preg_match($pattern, $t);
}


/**
 * Creates a weekly calendar as PDF export (used for reservations and events)
 */
function ko_export_cal_weekly_view($module, $_size = '', $_start = '', $pages = '')
{
    global $ko_path, $BASE_PATH, $BASE_URL, $DATETIME;

    $_start = $_start != '' ? $_start : date('Y-m-d',
        mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'], $_SESSION['cal_jahr']));

    if ($module == 'daten') {
        ko_get_eventgruppen($items);
        $planSize = $_size != '' ? $_size : ko_get_userpref($_SESSION['ses_userid'], 'daten_pdf_week_length');
        if ($planSize == 1) {
            $weekday = 1;
            $filename = getLL('daten_filename_pdf') . strftime('%d%m%Y',
                    mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'],
                        $_SESSION['cal_jahr'])) . '_' . strftime('%H%M%S', time()) . '.pdf';
        } else {
            $weekday = ko_get_userpref($_SESSION['ses_userid'], 'daten_pdf_week_start');
            $filename = getLL('daten_filename_pdf') . strftime('%d%m%Y_%H%M%S', time()) . '.pdf';
        }
        $show_legend = ko_get_userpref($_SESSION['ses_userid'], 'daten_export_show_legend') == 1;
    } else {
        ko_get_resitems($items);
        $planSize = $_size != '' ? $_size : ko_get_userpref($_SESSION['ses_userid'], 'res_pdf_week_length');
        if ($planSize == 1) {
            $weekday = 1;
            $filename = getLL('res_filename_pdf') . strftime('%d%m%Y',
                    mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'],
                        $_SESSION['cal_jahr'])) . '_' . strftime('%H%M%S', time()) . '.pdf';
        } else {
            $weekday = ko_get_userpref($_SESSION['ses_userid'], 'res_pdf_week_start');
            $filename = getLL('res_filename_pdf') . strftime('%d%m%Y_%H%M%S', time()) . '.pdf';
        }
        $show_persondata = $_SESSION['ses_userid'] != ko_get_guest_id() || ko_get_setting('res_show_persondata') == 1;
        $show_purpose = $_SESSION['ses_userid'] != ko_get_guest_id() || ko_get_setting('res_show_purpose') == 1;
        $show_legend = ko_get_userpref($_SESSION['ses_userid'], 'res_export_show_legend') == 1;
    }
    if ($weekday == 0) {
        $weekday = 7;
    }
    if (!$planSize) {
        $planSize = 7;
    }


    $startDate = add2date($_start, 'day', $weekday - 1, true);
    $startStamp = strtotime($startDate);
    $endStamp = strtotime('+' . ($planSize - 1) . ' day', $startStamp);

    $maxHours = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_end') - ko_get_userpref($_SESSION['ses_userid'],
            'cal_woche_start');
    $startHour = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_start') - 1;

    $HourTitleWidth = 4;

    //Prepare PDF file
    define('FPDF_FONTPATH', $BASE_PATH . 'fpdf/schriften/');
    require_once($BASE_PATH . 'fpdf/mc_table.php');

    $pdf = new PDF_MC_Table('L', 'mm', 'A4');
    $pdf->Open();
    $pdf->SetAutoPageBreak(true, 1);
    $pdf->SetMargins(5, 25, 5);  //left, top, right
    if (file_exists($ko_path . 'fpdf/schriften/DejaVuSansCondensed.php')) {
        $pdf->AddFont('fontn', '', 'DejaVuSansCondensed.php');
    } else {
        $pdf->AddFont('fontn', '', 'arial.php');
    }
    if (file_exists($ko_path . 'fpdf/schriften/DejaVuSansCondensed-Bold.php')) {
        $pdf->AddFont('fontb', '', 'DejaVuSansCondensed-Bold.php');
    } else {
        $pdf->AddFont('fontb', '', 'arialb.php');
    }


    $pageCounter = 1;
    if ($pages == '') {
        $pages = 1;
    }
    for ($pageCounter = 1; $pageCounter <= $pages; $pageCounter++) {

        $pdf->SetTextColor(0, 0, 0);

        $pdf->AddPage();
        $pdf->SetLineWidth(0.1);

        $top = 18;
        $left = 5;

        //Title
        $pdf->SetFont('fontb', '', 11);
        $m = strftime('%B', $startStamp) == strftime('%B', $endStamp) ? '' : strftime(' %B ', $startStamp);
        $y = strftime('%Y', $startStamp) == strftime('%Y', $endStamp) ? '' : strftime('%Y', $startStamp);

        if ($planSize == 1) {
            $pdf->Text($left, $top - 6, getLL('module_' . $module) . strftime(' - %d. %B %Y', $endStamp));
        } else {
            $pdf->Text($left, $top - 6,
                getLL('module_' . $module) . strftime(' %d.', $startStamp) . $m . $y . strftime(' - %d. %B %Y',
                    $endStamp));
        }

        //Add logo in header (only if legend is not to be shown)
        $logo = ko_get_pdf_logo();
        if ($logo != '' && !$show_legend) {
            $pic = getimagesize($BASE_PATH . 'my_images' . '/' . $logo);
            $picWidth = 9 / $pic[1] * $pic[0];
            $pdf->Image($BASE_PATH . 'my_images' . '/' . $logo, 290 - $picWidth, 5, $picWidth);
        }

        //footer right
        $pdf->SetFont('fontn', '', 8);
        $person = ko_get_logged_in_person();
        $creator = $person['vorname'] ? $person['vorname'] . ' ' . $person['nachname'] : $_SESSION['ses_username'];
        $footerRight = sprintf(getLL('tracking_export_label_created'), strftime($DATETIME['dmY'] . ' %H:%M', time()),
            $creator);
        $pdf->Text(291 - $pdf->GetStringWidth($footerRight), 202, $footerRight);

        //footer left
        $pdf->Text($left, 202, $BASE_URL);

        //get some measures
        $hourHeight = floor((180 / $maxHours) * 10) / 10;
        $dayWidth = floor((286 / $planSize) * 10) / 10;

        //Go through all days
        $legend = array();
        $index = 0;
        while ($index < $planSize) {
            $index++;
            // draw title of the Day
            $pdf->SetFillColor(33, 66, 99);
            $pdf->SetDrawColor(255);
            $pdf->Rect($left, $top - 4, $dayWidth, 4, 'FD');

            //Get current date information
            $currentStamp = strtotime('+' . ($index - 1) . ' day', $startStamp);
            $day = strftime('%d', $currentStamp);
            $month = strftime('%m', $currentStamp);
            $year = strftime('%Y', $currentStamp);
            $weekday = strftime('%u', $currentStamp);


            if ($dayWidth < 17) {
                $title = strftime('%d', $currentStamp) . '.';
            } else {
                $title = strftime(($dayWidth > 24 ? '%A' : '%a') . ', %d.%m.', $currentStamp);
            }
            $pdf->SetFont('fontb', '', 7);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Text($left + $dayWidth / 2 - $pdf->GetStringWidth($title) / 2, $top - 1, $title);

            // draw frame of the day
            $pdf->SetDrawColor(180);
            $pdf->Rect($left, $top, $dayWidth, $hourHeight * $maxHours, 'D');

            // draw frame of each day
            $pos = $top;
            //find 12th hour
            $twelve = 12 - $startHour;
            for ($i = 1; $i <= $maxHours; $i++) {
                // Box for each hour
                if ($weekday == 7 && $planSize > 1) {  //sunday
                    $fillColor = $i == $twelve ? 180 : 210;
                    $fillMode = 'DF';
                } else {
                    if ($weekday == 6 && $planSize > 1) {  //saturday
                        $fillColor = $i == $twelve ? 210 : 230;
                        $fillMode = 'DF';
                    } else {
                        $fillColor = 210;
                        $fillMode = $i == $twelve ? 'DF' : 'D';
                    }
                }
                $pdf->SetFillColor($fillColor);
                $pdf->Rect($left, $pos, $dayWidth, $hourHeight, $fillMode);

                // draw the hours
                $pdf->SetFont('fontn', '', 7);
                $pdf->SetTextColor(80);
                $actTime = strtotime('+' . $startHour . ' hours', $startStamp);
                $hourTitle = strftime('%H', strtotime('+' . $i . ' hours', $actTime));
                $cPos = ($HourTitleWidth - $pdf->GetStringWidth($hourTitle)) / 2;
                $pdf->Text($left + $cPos, $pos + 3, $hourTitle);

                //Go to next day
                $pos = $pos + $hourHeight;
            }

            // get the events for the current day
            $date = "$year-$month-$day";
            $where = "WHERE (`startdatum` <= '$date' AND `enddatum` >= '$date')";

            if ($module == 'daten') {
                $table = 'ko_event';
                $where .= sizeof($_SESSION['show_tg']) > 0 ? " AND `eventgruppen_id` IN ('" . implode("','",
                        $_SESSION['show_tg']) . "') " : ' AND 1=2 ';
            } else {
                $table = 'ko_reservation';
                $where .= sizeof($_SESSION['show_items']) > 0 ? " AND `item_id` IN ('" . implode("','",
                        $_SESSION['show_items']) . "') " : ' AND 1=2 ';
            }

            //Add kota filter
            $kota_where = kota_apply_filter($table);
            if ($kota_where != '') {
                $where .= " AND ($kota_where) ";
            }

            $eventArr = db_select_data($table, $where,
                '*, TIMEDIFF( CONCAT(enddatum," ",endzeit), CONCAT(startdatum," ",startzeit)) AS duration ',
                'ORDER BY duration DESC');

            //Correct $eventArr in relation to events starting and / or ending outside of the choosen timeframe and add corners
            $sort = array();
            foreach ($eventArr as $ev) {
                $id = $ev['id'];

                //Set endtime to midnight for all day events
                if ($ev['startzeit'] == '00:00:00' && $ev['endzeit'] == '00:00:00') {
                    $ev['endzeit'] = '23:59:59';
                }
                if ($ev['endzeit'] == '24:00:00') {
                    $ev['endzeit'] = '23:59:59';
                }
                if ($ev['startzeit'] == '24:00:00') {
                    $ev['startzeit'] = '23:59:59';
                }

                $eventArr[$id]['startMin'] = substr($ev['startzeit'], 0, 2) * 60 + substr($ev['startzeit'], 3, 2);
                $eventArr[$id]['stopMin'] = substr($ev['endzeit'], 0, 2) * 60 + substr($ev['endzeit'], 3, 2);
                $eventStart = strtotime($ev['startdatum'] . ' ' . $ev['startzeit']);
                $eventEnd = strtotime($ev['enddatum'] . ' ' . $ev['endzeit']);

                $calStart = mktime($startHour + 1, 0, 0, $month, $day, $year);
                $calEnd = mktime($startHour + 1 + $maxHours, 0, 0, $month, $day, $year);

                //Set color
                if ($module == 'daten') {
                    $eventArr[$id]['eventgruppen_farbe'] = $items[$ev['eventgruppen_id']]['farbe'];
                    ko_set_event_color($eventArr[$id]);
                } else {
                    $eventArr[$id]['eventgruppen_farbe'] = $items[$ev['item_id']]['farbe'];
                }

                //Check start: Inside or outside of displayed time frame
                if ($eventStart < $calStart) {
                    $eventArr[$id]['startMin'] = 1;
                } else {
                    if ($eventStart > $calEnd) {
                        continue;
                    } else {
                        $eventArr[$id]['startMin'] = $eventArr[$id]['startMin'] - ($startHour + 1) * 60;
                        $eventArr[$id]['roundedCorners'] = '12';
                    }
                }

                //Check end: Inside or outside of displayed time frame
                if ($eventEnd <= $calStart) {
                    continue;
                } else {
                    if ($eventEnd > $calEnd) {
                        $eventArr[$id]['stopMin'] = $maxHours * 60;
                    } else {
                        $eventArr[$id]['stopMin'] = $eventArr[$id]['stopMin'] - ($startHour + 1) * 60;
                        $eventArr[$id]['roundedCorners'] .= '34';
                    }
                }

                $eventArr[$id]['duration'] = $eventArr[$id]['stopMin'] - $eventArr[$id]['startMin'];
                $sort[$id] = $eventArr[$id]['stopMin'] - $eventArr[$id]['startMin'];
            }//foreach(eventArr as ev)

            //Sort for duration
            arsort($sort);
            $new = array();
            foreach ($sort as $id => $d) {
                $new[$id] = $eventArr[$id];
            }
            $eventArr = $new;
            unset($sort);
            unset($new);

            //create matrix to diplay used columns.
            $colMatrix = array();
            $eventColPosition = array();
            foreach ($eventArr as $ev) {
                //check if column free
                $col = 1;
                for ($min = $ev['startMin']; $min < $ev['stopMin']; $min++) {
                    if ($colMatrix[$min][$col]['pos']) {
                        $col++;
                    }
                }

                //mark full columns
                for ($min = $ev['startMin']; $min < $ev['stopMin']; $min++) {
                    $colMatrix[$min][$col]['pos'] = $ev['id'];
                    //array to store columnposition for certain event
                    $eventColPosition[$ev['id']] = $col;
                }
            }

            //find stripewidth for the day
            $maxColumnCnt = 1;
            foreach ($colMatrix as $min) {
                $maxColumnCnt = max($maxColumnCnt, count($min));
            }
            $stripeWidth = ($dayWidth - $HourTitleWidth) / $maxColumnCnt;

            //loop through the events of this day to draw them
            foreach ($eventArr as $currEvent) {
                $eventStart = intval(str_replace('-', '', $currEvent['startdatum']));
                $eventEnd = intval(str_replace('-', '', $currEvent['enddatum']));
                $durationDays = $eventEnd - $eventStart;

                if (($currEvent['duration'] <= 0) && ($durationDays <= 0)) {
                    continue;
                }

                //Event group or res item
                $item = $module == 'daten' ? $items[$currEvent['eventgruppen_id']] : $items[$currEvent['item_id']];

                //Legend
                ko_add_color_legend_entry($legend, $currEvent, $item);

                //find position
                $sPos = $HourTitleWidth + ($stripeWidth * ($eventColPosition[$currEvent['id']])) - $stripeWidth;


                if ($eventColPosition[$currEvent['id']] < $maxColumnCnt) {
                    $free = array();
                    for ($j = $eventColPosition[$currEvent['id']] + 1; $j <= $maxColumnCnt; $j++) {
                        $free[$j] = true;
                        for ($i = $currEvent['startMin']; $i < $currEvent['stopMin']; $i++) {
                            if (isset($colMatrix[$i][$j])) {
                                $free[$j] = false;
                            }
                        }
                    }
                }
                $width = $stripeWidth;
                for ($j = $eventColPosition[$currEvent['id']] + 1; $j <= $maxColumnCnt; $j++) {
                    if (!$free[$j]) {
                        break;
                    }
                    $width += $stripeWidth;
                }

                $y = $top + ($currEvent['startMin'] * $hourHeight / 60);
                $height = ($currEvent['stopMin'] - $currEvent['startMin'] + 1) * $hourHeight / 60;

                //Get color from event group
                $hex_color = $currEvent['eventgruppen_farbe'];
                if (!$hex_color) {
                    $hex_color = 'aaaaaa';
                }
                $pdf->SetFillColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                    hexdec(substr($hex_color, 4, 2)));

                $pdf->RoundedRect($left + $sPos + 0.3, $y, $width - 0.3, $height - 0.2, 1.2,
                    $currEvent['roundedCorners'], 'F');

                //Prepare text for this event
                $eventText = array();
                $eventShortText = array();
                //Use event group and title for events
                if ($module == 'daten') {
                    $eventText[0] = $item['name'];
                    if (trim($currEvent['title']) != '') {
                        $eventText[1] .= $currEvent['title'] . "\n";
                    }
                    if (trim($currEvent['kommentar']) != '') {
                        $eventText[1] .= $currEvent['kommentar'];
                    }
                    $eventShortText[0] = $item['shortname'];
                    if (trim($currEvent['title']) != '') {
                        $eventShortText[1] .= $currEvent['title'] . "\n";
                    }
                    if (trim($currEvent['kommentar']) != '') {
                        $eventShortText[1] .= $currEvent['kommentar'];
                    }
                } //Use item, purpose and name for reservations
                else {
                    $eventText[0] = $item['name'];
                    if ($show_purpose && $currEvent['zweck'] != '') {
                        $eventText[1] = $currEvent['zweck'];
                    }
                    if ($show_persondata && trim($currEvent['name']) != '') {
                        $eventText[1] .= ($eventText[1] != '' ? ' - ' : '') . getLL('by') . ' ' . $currEvent['name'];
                    }
                    $eventShortText = $eventText;
                }

                //check if title is still empty (e.g. kommentar is empty)
                if (trim($eventText[0]) == '') {
                    $eventText[] = $item['name'];
                    $eventShortText[] = $item['shortname'];
                }
                if (trim($eventShortText[0]) == '') {
                    $eventShortText = $eventText;
                }
                $replace = array("\n" => ' ', "\r" => ' ', "\t" => ' ', "\v" => ' ');
                $eventText[0] = strtr(trim($eventText[0]), $replace);
                $eventText[1] = strtr(trim($eventText[1]), $replace);
                $eventShortText[0] = strtr(trim($eventShortText[0]), $replace);
                $eventShortText[1] = strtr(trim($eventShortText[1]), $replace);
                while (stristr($eventText[0], '  ') != false) {
                    $eventText[0] = str_replace('  ', ' ', $eventText[0]);
                }
                while (stristr($eventText[1], '  ') != false) {
                    $eventText[1] = str_replace('  ', ' ', $eventText[1]);
                }
                while (stristr($eventShortText[0], '  ') != false) {
                    $eventShortText[0] = str_replace('  ', ' ', $eventShortText[0]);
                }
                while (stristr($eventShortText[1], '  ') != false) {
                    $eventShortText[1] = str_replace('  ', ' ', $eventShortText[1]);
                }

                //prepare text to render
                $hex_color = ko_get_contrast_color($currEvent['eventgruppen_farbe'], '000000', 'ffffff');
                if (!$hex_color) {
                    $hex_color = '000000';
                }
                $pdf->SetTextColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                    hexdec(substr($hex_color, 4, 2)));

                //check if text is to be rendered vertically
                if ($width < 15) {
                    //Use shortText if text is too long
                    $pdf->SetFont('fontb', '', 7);
                    if ($pdf->GetStringWidth($eventText[0]) > $height) {
                        $eventText = $eventShortText;
                    }
                    //Shorten texts so they'll fit
                    $textLength0 = $pdf->GetStringWidth($eventText[0]);
                    while ($textLength0 > $height && strlen($eventText[0]) > 0) {
                        $eventText[0] = substr($eventText[0], 0, -1);
                        $textLength0 = $pdf->GetStringWidth($eventText[0]);
                    }
                    $pdf->SetFont('fontn', '', 7);
                    $textLength1 = $pdf->GetStringWidth($eventText[1]);
                    while ($textLength1 > $height && strlen($eventText[1]) > 0) {
                        $eventText[1] = substr($eventText[1], 0, -1);
                        $textLength1 = $pdf->GetStringWidth($eventText[1]);
                    }
                    $eventText[2] = ': ' . $eventText[1];
                    $textLength2 = $pdf->GetStringWidth($eventText[2]);
                    while ($textLength2 > $height - $textLength0 - 3 && strlen($eventText[2]) > 0) {
                        $eventText[2] = substr($eventText[2], 0, -1);
                        $textLength2 = $pdf->GetStringWidth($eventText[2]);
                    }

                    if ($width > 6.1) {
                        if ($textLength0 < $textLength1) {
                            $textLength0 = $textLength1;
                        }
                        $pdf->SetFont('fontb', '', 7);
                        $pdf->TextWithDirection($left + $sPos + 2.6, $y + $height / 2 + ($textLength0 / 2),
                            $eventText[0], $direction = 'U');
                        $pdf->SetFont('fontn', '', 7);
                        $pdf->TextWithDirection($left + $sPos + 5.5, $y + $height / 2 + ($textLength0 / 2),
                            $eventText[1], $direction = 'U');
                    } else {
                        $pdf->SetFont('fontb', '', 7);
                        $pdf->TextWithDirection($left + $sPos + ($width / 2) + 1,
                            $y + $height / 2 + (($textLength0 + 3 + $textLength2) / 2) - 1, $eventText[0],
                            $direction = 'U');
                        $pdf->SetFont('fontn', '', 7);
                        $pdf->TextWithDirection($left + $sPos + ($width / 2) + 1,
                            $y + $height / 2 + (($textLength0 + 3 + $textLength2) / 2) - 1 - $textLength0,
                            $eventText[2], $direction = 'U');
                    }
                } //Render text horizontally
                else {
                    $textPos = $y + 1.8;

                    //break Text if its too long
                    $pdf->SetXY($left + $sPos, $textPos - 0.7);
                    $pdf->SetFont('fontb', '', 7);
                    $titleHeight = ($pdf->NbLines($width, $eventText[0]));
                    $pdf->Multicell($width, 3, $eventText[0], 0, 'L');

                    //shorten text if it is too long
                    $pdf->SetFont('fontn', '', 7);
                    $textHeight = $pdf->NbLines($width, $eventText[1]) + 1;
                    if ($titleHeight == 2) {
                        $textHeigth = $textHeigth + 3;
                    }
                    while ($textHeight * 3 > $height && strlen($eventText[1]) > 0) {
                        if (false !== strpos($eventText[1], ' ')) {
                            //Remove a whole word if possible
                            $eventText[1] = substr($eventText[1], 0, strrpos($eventText[1], ' '));
                        } else {
                            //If no more word just remove last letter
                            $eventText[1] = substr($eventText[1], 0, -1);
                        }
                        $textHeight = $pdf->NbLines($width, $eventText[1]) + 1;
                    }
                    $pdf->SetX($left + $sPos);

                    $pdf->Multicell($width, 3, $eventText[1], 0, 'L');
                }

            }//foreach(eventArr as currEvent)
            $left += $dayWidth;
        }//while(index < planSize)


        //Add legend (only for two or more entries and userpref)
        if ($show_legend && sizeof($legend) > 1) {
            $right = $planSize * $dayWidth + 5;
            ko_cal_export_legend($pdf, $legend, ($top - 13.5), $right);
        }

        $startStamp += $planSize * 24 * 3600;
        $endStamp += $planSize * 24 * 3600;
    }


    $file = $BASE_PATH . 'download/pdf/' . $filename;

    $ret = $pdf->Output($file);
    return $filename;
}//	ko_export_cal_weekly_view()


function ko_export_cal_weekly_view_resource($_size = '', $_start = '')
{
    global $ko_path, $BASE_PATH, $BASE_URL, $DATETIME, $do_action;

    // Starting parameters
    $startDate = $_start != '' ? $_start : date('Y-m-d',
        mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'], $_SESSION['cal_jahr']));

    // get resitems, applies filter from $_SESSION['show_item']
    ko_get_resitems($items, '', sizeof($_SESSION['show_items']) > 0 ? 'where ko_resitem.id in (' . implode(',',
            $_SESSION['show_items']) . ')' : 'where 1=2');
    $planSize = $_size != '' ? $_size : ko_get_userpref($_SESSION['ses_userid'], 'res_pdf_week_length');
    if ($planSize == 1) {
        $weekday = 1;
        $filename = getLL('res_filename_pdf') . strftime('%d%m%Y',
                mktime(1, 1, 1, $_SESSION['cal_monat'], $_SESSION['cal_tag'],
                    $_SESSION['cal_jahr'])) . '_' . strftime('%H%M%S', time()) . '.pdf';
    } else {
        $weekday = ko_get_userpref($_SESSION['ses_userid'], 'res_pdf_week_start');
        $filename = getLL('res_filename_pdf') . strftime('%d%m%Y_%H%M%S', time()) . '.pdf';
    }

    if ($weekday == 0) {
        $weekday = 7;
    }
    if (!$planSize) {
        $planSize = 7;
    }


    $startDate = add2date($startDate, 'day', $weekday - 1, true);
    $startStamp = strtotime($startDate);
    $endStamp = strtotime('+' . ($planSize - 1) . ' day', $startStamp);

    $maxHours = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_end') - ko_get_userpref($_SESSION['ses_userid'],
            'cal_woche_start');
    $startHour = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_start') - 1;

    $HourTitleWidth = 4;

    //Prepare PDF file
    define('FPDF_FONTPATH', $BASE_PATH . 'fpdf/schriften/');
    require($BASE_PATH . 'fpdf/mc_table.php');

    $pdf = new PDF_MC_Table('L', 'mm', 'A4');
    $pdf->Open();
    $pdf->SetAutoPageBreak(true, 1);
    $pdf->SetMargins(5, 25, 5);  //left, top, right
    if (file_exists($ko_path . 'fpdf/schriften/DejaVuSansCondensed.php')) {
        $pdf->AddFont('fontn', '', 'DejaVuSansCondensed.php');
    } else {
        $pdf->AddFont('fontn', '', 'arial.php');
    }
    if (file_exists($ko_path . 'fpdf/schriften/DejaVuSansCondensed-Bold.php')) {
        $pdf->AddFont('fontb', '', 'DejaVuSansCondensed-Bold.php');
    } else {
        $pdf->AddFont('fontb', '', 'arialb.php');
    }


    //Create Resource-View
    $objectLabel = 'Objekt';
    $timeLabel = "";
    //$granularity = ($planSize == 1) ? 24 : 4;
    $granularity = 1;
    $itemMarginV = 0.4;
    $itemMarginH = 0.4;
    $fontSizeItems = 8;
    $fontSizeTime = 6;
    $timeMarginV = 0.45;
    $labelMarginV = 0.2;
    $labelMarginH = 0.2;
    $maxPossCellHeight = 16;
    $minPossCellHeight = 8;
    $resWidth = 18;
    $marginBetwItemLines = 1;

    $timeDelimiter = array();

    // first & last hour in calendar:
    $hour_start = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_start');
    if ($hour_start == '') {
        $hour_start = "00:00";
        $timeDelimiter[] = $hour_start;
    } else {
        if (strlen($hour_start) == 1) {
            $hour_start = "0" . $hour_start . ":00";
            if (ko_has_time_format($hour_start) != 1) {
                $timeDelimiter[] = "00:00";
                koNotifier::Instance()->addNotice(1, $do_action, array($hour_start, 'first', '00:00'));
            } else {
                $timeDelimiter[] = $hour_start;
            }
        } else {
            if (strlen($hour_start) == 2) {
                $hour_start = $hour_start . ":00";
                if (ko_has_time_format($hour_start) != 1) {
                    $timeDelimiter[] = "00:00";
                    koNotifier::Instance()->addNotice(1, $do_action, array($hour_start, 'first', '00:00'));
                } else {
                    $timeDelimiter[] = $hour_start;
                }
            } else {
                $timeDelimiter[] = "00:00";
                koNotifier::Instance()->addNotice(1, $do_action, array($hour_start, 'first', '00:00'));
            }
        }
    }

    // add intermediate times
    $intermediateTimes = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_intermediate_times');
    $imTimesA = explode(';', $intermediateTimes);

    foreach ($imTimesA as $time) {
        if ($time != '') {
            $timeDelimiter[] = $time;
        }
    }

    // last hour in calendar:
    $hour_end = ko_get_userpref($_SESSION['ses_userid'], 'cal_woche_end');
    if ($hour_end == '') {
        $hour_end = "23:59";
        $timeDelimiter[] = $hour_end;
    } else {
        if (strlen($hour_end) == 1) {
            $hour_end = "0" . $hour_end . ":00";
            if (ko_has_time_format($hour_end) != 1) {
                $timeDelimiter[] = "23:59";
                koNotifier::Instance()->addNotice(1, $do_action, array($hour_end, 'last', '23:59'));
            } else {
                $timeDelimiter[] = $hour_end;
            }
        } else {
            if (strlen($hour_end) == 2) {
                $hour_end = $hour_end . ":00";
                if (ko_has_time_format($hour_end) != 1) {
                    $timeDelimiter[] = "23:59";
                    koNotifier::Instance()->addNotice(1, $do_action, array($hour_end, 'last', '23:59'));
                } else {
                    $timeDelimiter[] = $hour_end;
                }
            } else {
                $timeDelimiter[] = "23:59";
                koNotifier::Instance()->addNotice(1, $do_action, array($hour_end, 'last', '23:59'));
            }
        }
    }

    $rows = sizeof($timeDelimiter) - 1;

    $pdf->SetFont('fontn', '', $fontSizeTime);
    $timeWidth = $pdf->GetStringWidth('00:00') + 1;

    //Calculate the height of each field and the total number of pages
    $maxPossItemHeight = $maxPossCellHeight * $rows;
    $minPossItemHeight = $minPossCellHeight * $rows;
    $noItems = sizeof($items);
    $pageH = (210 - 20 - 2 * 4 - 3);
    $itemHeight = $pageH / $noItems;
    if ($itemHeight < $minPossItemHeight) {
        $itemsPPage = floor($pageH / $minPossItemHeight);
        $itemHeight = $pageH / $itemsPPage;
    } else {
        if ($itemHeight > $maxPossItemHeight) {
            $itemsPPage = ceil($pageH / $maxPossItemHeight);
            $itemHeight = $pageH / $itemsPPage;
            if ($itemHeight > $maxPossItemHeight) {
                $itemHeight = $maxPossItemHeight;
            }
        }
    }

    $cellHeight = $itemHeight / $rows;

    //Calculate the width of a day
    $days = $planSize;
    $pageW = 297 - 10;
    $dayWidth = ($pageW - $resWidth - $timeWidth) / $days;

    // Shorten Item Description
    foreach ($items as $key => $item) {
        $firstIter = true;
        while ($pdf->NBLines($resWidth,
                $items[$key]['name']) * (ko_fontsize_to_mm($fontSizeItems) + $marginBetwItemLines) > $itemHeight && $items[$key]['name'] != '..') {
            if ($firstIter) {
                $items[$key]['name'] .= '..';
            } else {
                $items[$key]['name'] = substr($items[$key]['name'], 0, strlen($items[$key]['name']) - 3) . '..';
            }
            $firstIter = false;
        }
    }

    $firstOnPage = true;

    $itemCounter = 1;

    $top = 18;
    $left = 5;

    //Go through resources and draw the corresponding lines
    foreach ($items as $item) {

        // add new page
        if ($top > 5 + 4 + $pageH || $firstOnPage) {

            $pdf->AddPage();
            $pdf->SetLineWidth(0.1);

            $top = 18;
            $left = 5;

            //Title
            $pdf->SetFont('fontb', '', 11);
            $m = strftime('%B', $startStamp) == strftime('%B', $endStamp) ? '' : strftime(' %B ', $startStamp);
            $y = strftime('%Y', $startStamp) == strftime('%Y', $endStamp) ? '' : strftime('%Y', $startStamp);

            if ($planSize == 1) {
                $pdf->Text($left, $top - 6,
                    getLL('reservation_export_pdf_title') . strftime(' - %d. %B %Y', $endStamp));
            } else {
                $pdf->Text($left, $top - 6, getLL('reservation_export_pdf_title') . strftime(' %d.',
                        $startStamp) . $m . $y . strftime(' - %d. %B %Y', $endStamp));
            }

            //Add logo in header (only if legend is not to be shown)
            $logo = ko_get_pdf_logo();
            if ($logo != '') {
                $pic = getimagesize($BASE_PATH . 'my_images' . '/' . $logo);
                $picWidth = 9 / $pic[1] * $pic[0];
                $pdf->Image($BASE_PATH . 'my_images' . '/' . $logo, 290 - $picWidth, 5, $picWidth);
            }

            //footer right
            $pdf->SetFont('fontn', '', 8);
            $person = ko_get_logged_in_person();
            $creator = $person['vorname'] ? $person['vorname'] . ' ' . $person['nachname'] : $_SESSION['ses_username'];
            $footerRight = sprintf(getLL('tracking_export_label_created'),
                strftime($DATETIME['dmY'] . ' %H:%M', time()), $creator);
            $pdf->Text(291 - $pdf->GetStringWidth($footerRight), 202, $footerRight);

            //Draw resource label
            $pdf->SetFillColor(33, 66, 99);
            $pdf->SetDrawColor(255);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFontSize($fontSizeItems);
            $pdf->Rect($left, $top - 4, $resWidth, 4, 'FD');
            $pdf->Text($left + $resWidth / 2 - $pdf->GetStringWidth($objectLabel) / 2, $top - 1, $objectLabel);

            $left += $resWidth;

            $pdf->Rect($left, $top - 4, $timeWidth, 4, 'FD');
            $pdf->Text($left + $timeWidth / 2 - $pdf->GetStringWidth($timeLabel) / 2, $top - 1, $timeLabel);

            $left += $timeWidth;

            $index = 0;

            //Draw day labels and boxes
            while ($index < $planSize) {
                $index++;

                //Get current date information
                $currentStamp = strtotime('+' . ($index - 1) . ' day', $startStamp);
                $date = strftime('%d.%m.%Y', $currentStamp);

                $weekday = strftime('%a', $currentStamp);
                $weekday = substr($weekday, 0, strlen($weekday) - 1);

                $pdf->SetFillColor(33, 66, 99);
                $pdf->SetDrawColor(255);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Rect($left, $top - 4, $dayWidth, 4, 'FD');
                $pdf->Text($left + $dayWidth / 2 - $pdf->GetStringWidth($date) / 2, $top - 1, $weekday . ', ' . $date);

                $left += $dayWidth;
            }

            $left = 5;
            $top += 4;

            $firstOnPage = true;
        }

        //Print item name
        $pdf->SetFillColor(33, 66, 99);
        $pdf->SetDrawColor(255);
        $pdf->SetTextColor(255);
        $pdf->SetFontSize($fontSizeItems);
        $pdf->Rect($left, $top - 4, $resWidth, $itemHeight, 'FD');
        $pdf->SetXY($left, $top - 4);
        $pdf->MultiCell($resWidth, ko_fontsize_to_mm($fontSizeItems) + $marginBetwItemLines, $item['name'], 0, 'L');


        $left += $resWidth;

        $startDate = date('Y-m-d', $startStamp);
        $endDate = date('Y-m-d', $endStamp);

        // draw time boxes
        $index = 0;
        $pdf->SetFillColor(243, 243, 243);
        $pdf->SetDrawColor(255);
        $pdf->SetTextColor(243, 243, 243);
        for ($j = 0; $j < $rows; $j++) {
            $pdf->Rect($left, $top - 4 + $j * $cellHeight, $timeWidth, $cellHeight, 'FD');
        }
        $left += $timeWidth;

        //Draw entry boxes
        while ($index < $planSize) {
            $index++;
            for ($i = 0; $i < $granularity; $i++) {
                for ($j = 0; $j < $rows; $j++) {
                    $pdf->Rect($left + $i * $dayWidth / $granularity, $top - 4 + $j * $cellHeight,
                        $dayWidth / $granularity, $cellHeight, 'FD');
                }
            }
            $left += $dayWidth;
        }

        // draw lines between days
        $left = 5 + $resWidth + $timeWidth;
        $index = 0;
        $oldLineWidth = $pdf->LineWidth;
        $pdf->SetLineWidth(0.2);
        $pdf->SetDrawColor(150);
        while ($index <= $planSize) {
            $index++;
            if ($index == $planSize + 1 && $firstOnPage) {
                $pdf->Line($left, 15 - 1, $left, $top - 4 + $itemHeight);
            } else {
                $pdf->Line($left, $top - 4, $left, $top - 4 + $itemHeight);
            }
            $left += $dayWidth;
        }
        $pdf->SetLineWidth($oldLineWidth);
        $pdf->SetDrawColor(255);

        $left = 5 + $resWidth;

        // draw line to seperate from earlier object
        if (!$firstOnPage) {
            $oldLineWidth = $pdf->LineWidth;
            $pdf->SetLineWidth(0.2);
            $pdf->SetDrawColor(150);
            $pdf->Line($left, $top - 4, $left + $timeWidth + $planSize * $dayWidth, $top - 4);
            $pdf->SetLineWidth($oldLineWidth);
            $pdf->SetDrawColor(255);
        }

        $left = 5 + $resWidth;

        // get reservations for current id, may contain reservations that don't overlap with de displayed interval
        $id = $item['id'];
        $where = "WHERE ((`startdatum` <= '$startDate' AND `enddatum` >= '$startDate') OR (`startdatum` <= '$endDate' AND `enddatum` >= '$endDate') OR (`startdatum` >= '$startDate' AND `enddatum` <= '$endDate')) AND (`item_id` = '$id') ";
        $reservations = db_select_data('ko_reservation', $where, '*', 'order by `startdatum` asc', '', false, true);
        $unixTimesT = array();

        $hour = 0;
        $minute = 0;

        $startUnix = $startStamp;
        $endUnix = $endStamp + 3600 * 24 - 1;

        $unixTimes = array();
        foreach ($reservations as $res) {
            if ($res['startdatum'] == $res['enddatum'] && $res['startzeit'] == '00:00:00' && $res['startzeit'] == $res['endzeit']) {
                $res['endzeit'] = '23:59:59';
            }
            $unixTimesT[] = array(
                'corners' => '1234',
                'start' => strtotime($res['startdatum'] . ' ' . $res['startzeit']),
                'end' => strtotime($res['enddatum'] . ' ' . $res['endzeit']),
                'zweck' => $res['zweck'],
                'startzeit' => $res['startzeit']
            );
        }


        // kick results that don't overlap with the desired timespan
        foreach ($unixTimesT as $ut) {
            if ($ut['end'] >= $startUnix && $ut['start'] <= $endUnix) {
                $unixTimes[] = $ut;
            }
        }

        // convert $timeDelimiter s to unix timeformat -> $unixDelimiter
        $unixDelimiter = array();
        foreach ($timeDelimiter as $td) {
            sscanf($td, "%d:%d", $hour, $minute);
            $unixDelimiter[] = ($hour * 3600 + $minute * 60);
        }

        $duration = array();
        $resultRows = array();

        // calculate difference between $unixDelimiter s
        for ($i = 0; $i < $rows; $i++) {
            $duration[] = ((int)$unixDelimiter[$i + 1]) - ((int)$unixDelimiter[$i]);
            $resultRows[] = array();
        }

        // seconds of a day
        $dayS = 24 * 3600;

        // calculate the entries in the table, switch on whether there will be 1 or more rows per day
        if ($rows > 1) {

            $dayTime = 0;
            $day = 0;

            $prevDayTime = -1;
            $prevEntry = -1;

            foreach ($unixTimes as $k => $ut) {
                $onSameRes = true;
                $belongsToPrevious = false;
                while ($onSameRes) {
                    if ($ut['start'] < $startUnix + $day * $dayS + $unixDelimiter[$dayTime]) {
                        $entry = array('start' => $day * $duration[$dayTime]);
                        $entry['belongsToPrevious'] = $belongsToPrevious;
                        if ($belongsToPrevious) {
                            $entry['prevDayTime'] = $prevDayTime;
                            $entry['prevEntry'] = $prevEntry;
                        } else {
                            $entry['drawText'] = true;
                        }
                        $entry['zweck'] = $ut['zweck'];
                        $entry['startzeit'] = $ut['startzeit'];
                        if ($ut['end'] <= $startUnix + $day * $dayS + ($unixDelimiter[$dayTime + 1]) && $ut['end'] >= $startUnix + $day * $dayS + $unixDelimiter[$dayTime]) {
                            $entry['end'] = $ut['end'] - $startUnix - $day * $dayS - $unixDelimiter[$dayTime] + $day * $duration[$dayTime];
                            $entry['corners'] = '23';

                            /**
                             * check whether the width of the current portion of a reservation is bigger than the previous one
                             * based on the result, decide where to draw the start_time and the purpose
                             **/
                            if ($entry['belongsToPrevious'] && !$resultRows[$entry['prevDayTime']][$entry['prevEntry']]['belongsToPrevious']) {
                                $width = $entry['end'] - $entry['start'];
                                $prevWidth = $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['end'] - $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['start'];
                                if ($width / $duration[$dayTime] > $prevWidth / $duration[$entry['prevDayTime']]) {
                                    $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = false;
                                    $entry['drawText'] = true;
                                } else {
                                    $entry['drawText'] = false;
                                    $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = true;
                                }
                            }
                            $resultRows[$dayTime][] = $entry;
                            $onSameRes = false;
                            $belongsToPrevious = false;
                        } else {
                            if ($ut['end'] > $startUnix + $day * $dayS + ($unixDelimiter[$dayTime + 1])) {
                                $entry['end'] = ($day + 1) * $duration[$dayTime];
                                $entry['corners'] = '';

                                /**
                                 * check whether the width of the current portion of a reservation is bigger than the previous one
                                 * based on the result, decide where to draw the start_time and the purpose
                                 **/
                                if ($entry['belongsToPrevious'] && !$resultRows[$entry['prevDayTime']][$entry['prevEntry']]['belongsToPrevious']) {
                                    $width = $entry['end'] - $entry['start'];
                                    $prevWidth = $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['end'] - $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['start'];
                                    if ($width / $duration[$dayTime] > $prevWidth / $duration[$entry['prevDayTime']]) {
                                        $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = false;
                                        $entry['drawText'] = true;
                                    } else {
                                        $entry['drawText'] = false;
                                        $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = true;
                                    }
                                }
                                $resultRows[$dayTime][] = $entry;

                                // store current array indexes in order to be able to reference this entry in the next loop iteration
                                $prevDayTime = $dayTime;
                                $prevEntry = sizeof($resultRows[$dayTime]) - 1;

                                $dayTime++;
                                $belongsToPrevious = true;
                            } else {
                                $onSameRes = false;
                            }
                        }
                    } else {
                        if ($ut['start'] >= $startUnix + $day * $dayS + $unixDelimiter[$dayTime + 1]) {
                            $dayTime++;
                        } else {
                            $entry = array('start' => $ut['start'] - $startUnix - $day * $dayS - $unixDelimiter[$dayTime] + $day * $duration[$dayTime]);
                            $entry['belongsToPrevious'] = $belongsToPrevious;
                            if ($belongsToPrevious) {
                                $entry['prevDayTime'] = $prevDayTime;
                                $entry['prevEntry'] = $prevEntry;
                            } else {
                                $entry['drawText'] = true;
                            }
                            $entry['zweck'] = $ut['zweck'];
                            $entry['startzeit'] = $ut['startzeit'];
                            if ($ut['end'] <= $startUnix + $day * $dayS + ($unixDelimiter[$dayTime + 1]) && $ut['end'] >= $startUnix + $day * $dayS + $unixDelimiter[$dayTime]) {
                                $entry['end'] = $ut['end'] - $startUnix - $day * $dayS - $unixDelimiter[$dayTime] + $day * $duration[$dayTime];
                                $entry['corners'] = '1234';

                                /**
                                 * check whether the width of the current portion of a reservation is bigger than the previous one
                                 * based on the result, decide where to draw the start_time and the purpose
                                 **/
                                if ($entry['belongsToPrevious'] && !$resultRows[$entry['prevDayTime']][$entry['prevEntry']]['belongsToPrevious']) {
                                    $width = $entry['end'] - $entry['start'];
                                    $prevWidth = $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['end'] - $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['start'];
                                    if ($width / $duration[$dayTime] > $prevWidth / $duration[$entry['prevDayTime']]) {
                                        $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = false;
                                        $entry['drawText'] = true;
                                    } else {
                                        $entry['drawText'] = false;
                                        $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = true;
                                    }
                                }
                                $resultRows[$dayTime][] = $entry;
                                $onSameRes = false;
                                $belongsToPrevious = false;
                            } else {
                                if ($ut['end'] > $startUnix + $day * $dayS + ($unixDelimiter[$dayTime + 1])) {
                                    $entry['end'] = ($day + 1) * $duration[$dayTime];
                                    $entry['corners'] = '41';

                                    /**
                                     * check whether the width of the current portion of a reservation is bigger than the previous one
                                     * based on the result, decide where to draw the start_time and the purpose
                                     **/
                                    if ($entry['belongsToPrevious'] && !$resultRows[$entry['prevDayTime']][$entry['prevEntry']]['belongsToPrevious']) {
                                        $width = $entry['end'] - $entry['start'];
                                        $prevWidth = $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['end'] - $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['start'];
                                        if ($width / $duration[$dayTime] > $prevWidth / $duration[$entry['prevDayTime']]) {
                                            $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = false;
                                            $entry['drawText'] = true;
                                        } else {
                                            $entry['drawText'] = false;
                                            $resultRows[$entry['prevDayTime']][$entry['prevEntry']]['drawText'] = true;
                                        }
                                    }
                                    $resultRows[$dayTime][] = $entry;

                                    // store current array indexes in order to be able to reference this entry in the next loop iteration
                                    $prevDayTime = $dayTime;
                                    $prevEntry = sizeof($resultRows[$dayTime]) - 1;

                                    $dayTime++;
                                    $belongsToPrevious = true;
                                } else {
                                    $onSameRes = false;
                                }
                            }
                        }
                    }

                    // if $dayTime exceeds number of $rows, increment $day and set $dayTime back to 0
                    if ($dayTime == $rows) {
                        $dayTime = 0;
                        $day++;
                        if ($day == $planSize) {
                            $onSameRes = false;
                        }
                    }
                }
            }
        } else {
            if ($rows == 1) {
                $hourStartUnix = $unixDelimiter[0];
                $hourEndUnix = $unixDelimiter[1];

                $startUnix += $hourStartUnix;
                if (sizeof($unixTimes) > 0) {
                    if ($unixTimes[0]['start'] < $startUnix) {
                        $unixTimes[0]['start'] = $startUnix;
                        $unixTimes[0]['corners'] == '1234' ? $unixTimes[0]['corners'] = '23' : $unixTimes[0]['corners'] = '';
                    }
                    if ($unixTimes[sizeof($unixTimes) - 1]['end'] > $endUnix) {
                        $unixTimes[sizeof($unixTimes) - 1]['end'] = $endUnix;
                        $unixTimes[sizeof($unixTimes) - 1]['corners'] == '1234' ? $unixTimes[sizeof($unixTimes) - 1]['corners'] = '41' : $unixTimes[sizeof($unixTimes) - 1]['corners'] = '';
                    }
                }
                $dontShowInterval = $hourStartUnix + $dayS - $hourEndUnix;
                foreach ($unixTimes as $k => $ut) {
                    // correct start of reservation according to setting 'first hour in export'
                    $relToStart = $ut['start'] - $startUnix;
                    $modDays = $relToStart % $dayS;
                    $daysTo = floor($relToStart / $dayS);
                    if ($modDays > $dayS - $dontShowInterval) {
                        $unixTimes[$k]['start'] = ($daysTo + 1) * $dayS;
                        $unixTimes[$k]['corners'] == '1234' ? $unixTimes[$k]['corners'] = '23' : $unixTimes[$k]['corners'] = '';
                    } else {
                        $unixTimes[$k]['start'] = $relToStart;
                    }
                    $daysTo = floor($unixTimes[$k]['start'] / $dayS);
                    $unixTimes[$k]['start'] = $unixTimes[$k]['start'] - ($daysTo) * $dontShowInterval;

                    // correct start of reservation according to setting 'last hour in export'
                    $relToStart = $ut['end'] - $startUnix;
                    $modDays = $relToStart % $dayS;
                    $daysTo = floor($relToStart / $dayS);
                    if ($modDays > $dayS - $dontShowInterval) {
                        $unixTimes[$k]['end'] = ($daysTo + 1) * $dayS - $dontShowInterval;
                        $unixTimes[$k]['corners'] == '1234' ? $unixTimes[$k]['corners'] = '41' : $unixTimes[$k]['corners'] = '';
                    } else {
                        $unixTimes[$k]['end'] = $relToStart;
                    }
                    $daysTo = floor($unixTimes[$k]['end'] / $dayS);
                    $unixTimes[$k]['end'] = $unixTimes[$k]['end'] - ($daysTo) * $dontShowInterval;
                }

                foreach ($unixTimes as $ut) {
                    $ut['drawText'] = true;
                    $resultRows[0][] = $ut;
                }
            }
        }

        for ($i = 0; $i < $rows; $i++) {
            $planTime = $duration[$i] * $planSize;

            //Draw time label
            $pdf->SetFontSize($fontSizeTime);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(5 + $resWidth, $top - 4 + $timeMarginV);
            $pdf->SetDrawColor(0);
            $timeHeight = ko_fontsize_to_mm($fontSizeTime);
            $pdf->Cell($timeWidth, $timeHeight, $timeDelimiter[$i], 0, 0, "C");
            $pdf->SetXY(5 + $resWidth, $top - 4 + $cellHeight - $timeHeight - $timeMarginV);
            $pdf->Cell($timeWidth, $timeHeight, $timeDelimiter[$i + 1], 0, 0, "C");

            $left += $timeWidth;

            // draw reservations
            foreach ($resultRows[$i] as $ut) {
                $pdf->SetFillColor(33, 66, 99);
                $pdf->SetDrawColor(255);
                $pdf->SetTextColor(255, 255, 255);
                $hex_color = $item['farbe'];
                if (!$hex_color) {
                    $hex_color = 'aaaaaa';
                }
                $pdf->SetFillColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                    hexdec(substr($hex_color, 4, 2)));
                $leftValue = $left + ($ut['start'] / $planTime * ($pageW - $resWidth - $timeWidth)) + $itemMarginH;
                $topValue = $top - 4 + $itemMarginV;
                $width = (($ut['end'] - $ut['start']) / $planTime * ($pageW - $resWidth - $timeWidth)) - 2 * $itemMarginH;
                $height = $cellHeight - 2 * $itemMarginV;

                $pdf->RoundedRect($leftValue, $topValue, $width, $height,
                    min($cellHeight, (($ut['end'] - $ut['start']) / $planTime * ($pageW - $resWidth))) / 10,
                    $ut['corners'], 'F');

                // draw start_time and purpose of reservation, if possible
                if ($ut['drawText'] === true) {
                    $text = $ut['zweck'];
                    $time = substr($ut['startzeit'], 0, sizeof($ut['startzeit']) - 4);

                    $size = 9;
                    if (2 * ko_fontsize_to_mm($size) > $height) {
                        $size = floor(ko_mm_to_fontsize(($height) / 2));
                    }

                    $textMargin = ($labelMarginH + $width / 30 > 2 ? 2 : $labelMarginH + $width / 30);
                    $fits = ko_get_fitting_text_width($pdf, $width - $textMargin, $text, $time, $size);

                    if ($fits) {
                        $hex_color = ko_get_contrast_color($hex_color, '000000', 'FFFFFF');
                        $pdf->SetTextColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                            hexdec(substr($hex_color, 4, 2)));
                        $pdf->SetFontSize($size);
                        $marginBetwLabels = ($height - 2 * ko_fontsize_to_mm($size)) / 3;
                        $pdf->Text($leftValue + $textMargin,
                            $topValue + 0.8 * ko_fontsize_to_mm($size) + $marginBetwLabels, $time);
                        $pdf->Text($leftValue + $textMargin,
                            $topValue + 1.8 * ko_fontsize_to_mm($size) + 2 * $marginBetwLabels, $text);
                    }
                }
            }

            $left = 5 + $resWidth;
            $top += $cellHeight;
        }

        $left = 5;

        // add line on bottom of table, if a new page will be added next or this was the last item
        if ($itemCounter == sizeof($items) || $top > 5 + 4 + $pageH) {
            $oldLineWidth = $pdf->LineWidth;
            $pdf->SetLineWidth(0.2);
            $pdf->SetDrawColor(150);
            $pdf->Line($left, $top - 4, $left + $timeWidth + $resWidth + $planSize * $dayWidth, $top - 4);
            $pdf->SetLineWidth($oldLineWidth);
            $pdf->SetDrawColor(255);
        }

        $left = 5;

        $firstOnPage = false;

        $itemCounter++;

    }


    $file = $BASE_PATH . 'download/pdf/' . $filename;

    $ret = $pdf->Output($file);
    return $filename;
}//	ko_export_cal_weekly_view_resource()


/**
 * Exportiert einen Monat als PDF
 */
function ko_export_cal_one_month(
    &$pdf,
    $monat,
    $jahr,
    $kw,
    $day,
    $titel,
    $show_comment = false,
    $show_legend = false,
    $legend = array()
)
{
    global $BASE_URL, $BASE_PATH, $DATETIME;

    $monthly_title = ko_get_userpref($_SESSION['ses_userid'], 'daten_monthly_title');

    //Datums-Berechnungen
    //Start des Monats
    $startdate = date($jahr . "-" . $monat . "-01");
    $today = date("Y-m-d");
    $startofmonth = $date = $startdate;
    $month_name = strftime("%B", strtotime($date));
    $year_name = strftime("%Y", strtotime($date));

    //Den letzten Tag dieses Monats finden
    $endofmonth = add2date($date, "monat", 1, true);
    $endofmonth = add2date($endofmonth, "tag", -1, true);
    //Ende der letzten Woche dieses Monats finden
    $enddate = date_find_next_sunday($endofmonth);
    //Start der ersten Woche dieses Monats finden
    $date = date_find_last_monday($date);

    $testdate = $date;
    $dayofweek = $num_weeks = 0;
    while ((int)str_replace("-", "", $testdate) <= (int)str_replace("-", "", $endofmonth)) {
        $dayofweek++;
        $testdate = add2date($testdate, "tag", 1, true);
        if ($dayofweek == 7) {
            $num_weeks++;
            $dayofweek = 0;
        }
    }
    //Falls Sonntag letzter Tag im Monat, wieder eine Woche abziehen
    if ((int)$dayofweek == 0) {
        $num_weeks--;
    }


    $pdf->AddPage();

    //Spaltenbreiten f�r Tabelle
    $width_kw = 7;
    $width_day = 39.5;
    $height_title = 5;
    //$height_day = 9;
    $height_day = (223 * 0.8) / ($num_weeks + 1);
    $height_dayheader = 5;
    $height_event_default = 4;
    $offset_x = 1;
    $offset_y = 4;

    $top = 15;
    $left = 7;


    //Titel
    $pdf->SetFont('fontb', '', 11);
    $pdf->Text($left, $top - 3, "$titel $month_name $year_name");

    //Add logo in header
    $logo = ko_get_pdf_logo();
    if ($logo != '' && !$show_legend) {
        $pic = getimagesize($BASE_PATH . 'my_images' . '/' . $logo);
        $picWidth = 9 / $pic[1] * $pic[0];
        $pdf->Image($BASE_PATH . 'my_images' . '/' . $logo, 290 - $picWidth, $top - 10, $picWidth);
    }

    //Footer right
    $pdf->SetFont('fontn', '', 8);
    $person = ko_get_logged_in_person();
    $creator = $person['vorname'] ? $person['vorname'] . ' ' . $person['nachname'] : $_SESSION['ses_username'];
    $footerRight = sprintf(getLL('tracking_export_label_created'), strftime($DATETIME['dmY'] . ' %H:%M', time()),
        $creator);
    $pdf->Text(291 - $pdf->GetStringWidth($footerRight), 202, $footerRight);

    //Footer left
    $pdf->SetFont('fontn', '', 8);
    $pdf->Text($left, 202, $BASE_URL);


    //Tabellen-Header
    $pdf->SetTextColor(255);
    $pdf->SetLineWidth(0.1);
    $pdf->SetDrawColor(160);
    $pdf->SetFillColor(33, 66, 99);

    $x = $left;
    $y = $top;
    //KW
    $pdf->SetFont('fontn', '', 8);
    $pdf->Rect($x, $y, $width_kw, $height_title, "FD");
    $pdf->Text($x + $width_kw / 2 - $pdf->GetStringWidth("KW") / 2, $y + 3.5, "KW");
    $x += $width_kw;
    //Tagesnamen
    $monday = date_find_last_monday(date("Y-m-d"));
    $pdf->SetFont('fontb', '', 8);
    for ($i = 0; $i < 7; $i++) {
        $t = strftime("%A", strtotime(add2date($monday, "tag", $i, true)));
        $pdf->Rect($x, $y, $width_day, $height_title, "FD");
        $pdf->Text($x + ($width_day - $pdf->GetStringWidth($t)) / 2, $y + 3.5, $t);
        $x += $width_day;
    }

    $x = $left;
    $y += $height_title;

    //Alle anzuzeigenden Tage durchlaufen
    $dayofweek = $weekcounter = 0;
    while ((int)str_replace("-", "", $date) <= (int)str_replace("-", "", $enddate)) {
        $pdf->SetTextColor(0);
        $thisday = $day[(int)substr($date, 8, 2)];
        $thisday['tag'] = (int)substr($date, 8, 2);
        //KW ausgeben
        if ($dayofweek == 0) {
            $pdf->SetFillColor(200);
            $pdf->Rect($x, $y, $width_kw, $height_day, "FD");
            $pdf->SetFont('fontn', '', 8);
            $pdf->SetTextColor(80);
            $pdf->Text($x + $width_kw / 2 - $pdf->GetStringWidth($kw[$weekcounter]) / 2, $y + 5, $kw[$weekcounter]);
            $weekcounter++;
            $x += $width_kw;
        }
        //Tag vor und nach aktuellem Monat
        if (substr($date, 5, 2) != $monat) {
            $pdf->SetFillColor(230);
            $pdf->Rect($x, $y, $width_day, $height_day, "FD");
        } //Tage dieses Monates
        else {
            $pdf->Rect($x, $y, $width_day, $height_day, "D");
            //Tages-Nummer
            $pdf->SetFont('fontb', '', 8);
            $pdf->SetTextColor(80);
            $pdf->Text(($x + $width_day - $pdf->GetStringWidth($thisday["tag"]) - $offset_x), $y + $offset_y,
                $thisday["tag"]);
            $y_day = $y + $height_dayheader;
            //H�he der Termineintr�ge berechnen
            $num_events = sizeof($thisday["inhalt"]);
            //Add titles
            if ($show_comment) {
                foreach ($thisday["inhalt"] as $temp) {
                    if ($temp["kommentar"] != "") {
                        $num_events++;
                    }
                }
            }
            if ($num_events > 0) {
                $height_event = $height_event_default;
                if (($num_events * $height_event) > ($height_day - $height_dayheader)) {
                    $height_event = ($height_day - $height_dayheader) / $num_events;
                }
                $offset_y_events = 0.75 * $height_event;
                $height_event_1 = $height_event;
                foreach ($thisday["inhalt"] as $c) {
                    if ($show_comment && $c["kommentar"] != "") {
                        $height_event = 2 * $height_event_1;
                    } else {
                        $height_event = $height_event_1;
                    }

                    $color_hex = $c["farbe"] ? $c["farbe"] : "999999";
                    $pdf->SetFillColor(hexdec(substr($color_hex, 0, 2)), hexdec(substr($color_hex, 2, 2)),
                        hexdec(substr($color_hex, 4, 2)));
                    $pdf->Rect($x + 0.1, $y_day, $width_day - 0.2, $height_event, "F");
                    if ($num_events > 11) {
                        $pdf->SetFont('fontn', '', 5);
                        $font2 = 5;
                    } else {
                        if ($num_events > 8) {
                            $pdf->SetFont('fontn', '', 6);
                            $font2 = 5;
                        } else {
                            $pdf->SetFont('fontn', '', 7);
                            $font2 = 6;
                        }
                    }
                    $t = ($c['zeit'] != '' ? $c['zeit'] . ': ' : '') . ko_unhtml($c['text']);
                    //Use short text if long text is too long
                    if ($pdf->getStringWidth($t) > ($width_day - 2 * $offset_x)) {
                        $t = ($c['zeit'] != '' ? $c['zeit'] . ': ' : '') . ko_unhtml($c['short']);
                    }
                    //Truncate text if it is too long
                    while ($pdf->GetStringWidth($t) > ($width_day - 2 * $offset_x)) {
                        $t = substr($t, 0, -1);
                    }
                    $textcolor = ko_get_contrast_color($color_hex, '000000', 'ffffff');
                    $pdf->SetTextColor(hexdec(substr($textcolor, 0, 2)), hexdec(substr($textcolor, 2, 2)),
                        hexdec(substr($textcolor, 4, 2)));
                    $pdf->Text($x + $offset_x, $y_day + $offset_y_events, $t);

                    //Add title
                    if ($show_comment && $c["kommentar"]) {
                        $y_day += $height_event / 2;
                        $t = " " . ko_unhtml($c["kommentar"]);
                        $pdf->SetFont('fontn', '', $font2);
                        while ($pdf->GetStringWidth($t) > ($width_day - 2 * $offset_x)) {
                            $t = substr($t, 0, -1);
                        }
                        $pdf->Text($x + $offset_x, $y_day + $offset_y_events, $t);
                        $y_day += $height_event / 2;
                    } else {
                        $y_day += $height_event;
                    }

                }
            }//if(num_events > 0)
        }//if(DAY(date) != monat)
        $x += $width_day;
        $dayofweek++;
        $date = add2date($date, "tag", 1, true);
        if ($dayofweek == 7) {
            $dayofweek = 0;
            $y += $height_day;
            $x = $left;
        }
    }//while(date < enddate)


    //Add legend (only for two or more entries and userpref)
    if ($show_legend && sizeof($legend) > 1) {
        $right = $width_kw + 7 * $width_day + 7;
        ko_cal_export_legend($pdf, $legend, ($top - 9.5), $right);
    }
}//ko_export_cal_one_month()


function ko_get_time_as_string($event, $show_time, $mode = 'default')
{
    if ($show_time) {
        if ($event['startzeit'] == '00:00:00' && $event['endzeit'] == '00:00:00') {
            $time = getLL('time_all_day');
        } else {
            if ($mode == 'default') {
                if ($show_time == 1) {  //Only show start time
                    $time = substr($event['startzeit'], 3, 2) == '00' ? substr($event['startzeit'], 0,
                        2) : substr($event['startzeit'], 0, -3);
                } else {
                    if ($show_time == 2) {  //Show start and end time
                        $time = substr($event['startzeit'], 3, 2) == '00' ? substr($event['startzeit'], 0,
                            2) : substr($event['startzeit'], 0, -3);
                        $time .= '-';
                        $time .= substr($event['endzeit'], 3, 2) == '00' ? substr($event['endzeit'], 0,
                            2) : substr($event['endzeit'], 0, -3);
                    }
                }
            } else {
                if ($mode == 'first') {
                    $time = getLL('time_from') . ' ';
                    $time .= substr($event['startzeit'], 3, 2) == '00' ? substr($event['startzeit'], 0,
                        2) : substr($event['startzeit'], 0, -3);
                } else {
                    if ($mode == 'middle') {
                        $time = getLL('time_all_day');
                    } else {
                        if ($mode == 'last') {
                            $time = getLL('time_to') . ' ';
                            $time .= substr($event['endzeit'], 3, 2) == '00' ? substr($event['endzeit'], 0,
                                2) : substr($event['endzeit'], 0, -3);
                        }
                    }
                }
            }
        }
    } else {
        $time = '';
    }

    return $time;
}//ko_get_time_as_string()


function ko_export_cal_pdf_year($module, $_month, $_year, $_months = 0)
{
    global $BASE_PATH, $BASE_URL, $DATETIME;

    // Starting parameters
    $startMonth = $_month ? $_month : '01';
    $startYear = $_year ? $_year : date('Y');
    $planSize = $_months > 0 ? $_months : 12;
    $stripeWidth = 2.5;
    $maxMultiDayColumns = 10;  //Maximum number of columns to be used for multi-day events
    $showWeekNumbers = true;  //Show week numbers on each monday

    $endYear = $startYear;
    $endMonth = $startMonth + $planSize - 1;
    while ($endMonth > 12) {
        $endMonth -= 12;
        $endYear += 1;
    }


    $legend = array();

    //Events
    if ($module == 'daten') {
        $title_mode = ko_get_userpref($_SESSION['ses_userid'], 'daten_monthly_title');
        $useEventGroups = $_SESSION['show_tg'];
        ko_get_eventgruppen($egs);

        $page_title = getLL('daten_events');
        $db_table = 'ko_event';
        $db_group_field = 'eventgruppen_id';
        $filename_prefix = getLL('daten_filename_pdf');

        $show_legend = ko_get_userpref($_SESSION['ses_userid'], 'daten_export_show_legend') == 1;
    } //Reservations
    else {
        if ($module == 'reservation') {
            $title_mode = ko_get_userpref($_SESSION['ses_userid'], 'res_monthly_title');
            $useEventGroups = $_SESSION['show_items'];
            ko_get_resitems($egs);

            $page_title = getLL('res_reservations');
            $db_table = 'ko_reservation';
            $db_group_field = 'item_id';
            $filename_prefix = getLL('res_filename_pdf');

            $show_legend = ko_get_userpref($_SESSION['ses_userid'], 'res_export_show_legend') == 1;
        } else {
            return false;
        }
    }


    // create Montharray
    //$MonthArr = array (str_to_2($startMonth));
    $index = 0;
    $monthcnt = $startMonth;
    for ($index = 0; $index < $planSize; $index++) {
        $monthArr[] = $startMonth + $index > 12 ? '01' : str_to_2($startMonth + $index);
    }

    // find offset of each month
    $offsetDate = $startYear . "-" . $startMonth . "-01";
    $offsetDate = date_find_next_sunday($offsetDate);

    $maxDays = 0;
    $year = $startYear;
    for ($i = 0; $i < $planSize; $i++) {
        $month = $startMonth + $i;
        if ($month > 12) {
            $month = $month - 12;
            $year = $startYear + 1;
        }
        $offsetDate = 7 - (int)substr(date_find_next_sunday($year . '-' . $month . '-01'), 8, 2);
        $offsetDayArr[str_to_2($month) . $year] = $offsetDate;

        $maxDays = max($maxDays, $offsetDate + (int)strftime('%d',
                mktime(1, 1, 1, ($month == 12 ? 1 : $month + 1), 0, ($month == 12 ? ($year + 1) : $year))));
    }


    //Start PDF file
    define('FPDF_FONTPATH', $BASE_PATH . 'fpdf/schriften/');
    require($BASE_PATH . 'fpdf/fpdf.php');

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->Open();
    $pdf->SetAutoPageBreak(true, 1);
    $pdf->SetMargins(5, 25, 5);  //left, top, right
    if (file_exists($BASE_PATH . 'fpdf/schriften/DejaVuSansCondensed.php')) {
        $pdf->AddFont('fontn', '', 'DejaVuSansCondensed.php');
    } else {
        $pdf->AddFont('fontn', '', 'arial.php');
    }
    if (file_exists($BASE_PATH . 'fpdf/schriften/DejaVuSansCondensed-Bold.php')) {
        $pdf->AddFont('fontb', '', 'DejaVuSansCondensed-Bold.php');
    } else {
        $pdf->AddFont('fontb', '', 'arialb.php');
    }

    $pdf->AddPage();
    $pdf->SetLineWidth(0.1);


    $top = 18;
    $left = 5;

    //Title
    $pdf->SetFont('fontb', '', 13);
    $pdf->Text($left, $top - 7,
        $page_title . "  " . strftime('%B %Y', mktime(1, 1, 1, $startMonth, 1, $startYear)) . " - " . strftime('%B %Y',
            mktime(1, 1, 1, $endMonth, 1, $endYear)));

    //Logo
    $logo = ko_get_pdf_logo();
    if ($logo && !$show_legend) {
        $pic = getimagesize($BASE_PATH . 'my_images' . '/' . $logo);
        $picWidth = 9 / $pic[1] * $pic[0];
        $pdf->Image($BASE_PATH . 'my_images' . '/' . $logo, 290 - $picWidth, 5, $picWidth);
    }


    //footer right
    $pdf->SetFont('fontn', '', 8);
    $person = ko_get_logged_in_person();
    $creator = $person['vorname'] ? $person['vorname'] . ' ' . $person['nachname'] : $_SESSION['ses_username'];
    $footerRight = sprintf(getLL('tracking_export_label_created'), strftime($DATETIME['dmY'] . ' %H:%M', time()),
        $creator);
    $footerStart = 291 - $pdf->GetStringWidth($footerRight);
    $pdf->Text($footerStart, 202, $footerRight);

    //footer left
    $pdf->Text($left, 202, $BASE_URL);

    //get some mesures
    $dayHeight = 180 / $maxDays;
    $dayHeight = floor($dayHeight * 10) / 10;
    $monthWidth = 286 / $planSize;
    $monthWidth = floor($monthWidth * 10) / 10;


    // draw lines of each month
    foreach ($offsetDayArr as $key => $offsetDays) {
        // draw title of the month
        $pdf->SetFillColor(33, 66, 99);
        $pdf->Rect($left, $top - 3, $monthWidth, 3, "FD");
        $pdf->SetFont('fontn', '', 7);
        $pdf->SetTextColor(255, 255, 255);
        $month = substr($key, 0, 2);
        $year = substr($key, 2);
        $title = strftime('%B', strtotime('2000-' . $month . '-10'));
        $pdf->Text($left + $monthWidth / 2 - $pdf->GetStringWidth($title) / 2, $top - 0.7, $title);

        // get the number of days of the month
        $numDays = (int)strftime('%d',
            mktime(1, 1, 1, ($month == 12 ? 1 : $month + 1), 0, ($month == 12 ? ($year + 1) : $year)));


        // draw frame of the month
        $pdf->Rect($left, $top, $monthWidth, $dayHeight * $maxDays, 'D');
        //Fill areas above and below month
        $pdf->SetFillColor(150, 150, 150);
        $pdf->Rect($left, $top, $monthWidth, $offsetDays * $dayHeight, 'F');
        $pdf->Rect($left, $top + ($offsetDays + $numDays) * $dayHeight, $monthWidth,
            ($maxDays - $offsetDays - $numDays) * $dayHeight, 'F');
        // draw frame of each day
        $pos = $top + $offsetDays * $dayHeight;
        for ($i = 1; $i <= $numDays; $i++) {
            // Set color according to day of the week (mark weekends)
            switch (date('w', mktime(1, 1, 1, $month, $i, $year))) {
                case 0:
                    $pdf->SetFillColor(189);
                    break;
                case 6:
                    $pdf->SetFillColor(226);
                    break;
                default:
                    $pdf->SetFillColor(255);
            }
            // Box for each day
            $pdf->Rect($left, $pos, $monthWidth, $dayHeight, 'DF');

            // draw frame for the dates

            // Set color according to day of the week (mark weekends)
            switch (date('w', mktime(1, 1, 1, $month, $i, $year))) {
                case 0:
                    $pdf->SetFillColor(189);
                    $pdf->Rect($left, $pos + 0.1, 3, $dayHeight - 0.2, 'F');
                    break;
                case 6:
                    $pdf->SetFillColor(226);
                    $pdf->Rect($left, $pos + 0.1, 3, $dayHeight - 0.2, 'F');
                    break;
                default:
                    $pdf->SetFillColor(255);
            }


            // draw the dates
            $pdf->SetFont('fontn', '', 5);
            $pdf->SetTextColor(0, 0, 0);
            $weekDay = substr(strftime('%a', mktime(1, 1, 1, $month, $i, $year)), 0, 2);
            $cPos = (3 - $pdf->GetStringWidth($weekDay)) / 2;
            $pdf->Text($left + $cPos, $pos + 2, $weekDay);
            $cPos = (3 - $pdf->GetStringWidth($i)) / 2;
            $pdf->Text($left + $cPos, $pos + 4, $i);

            //Go to next day
            $pos = $pos + $dayHeight;
        }


        // get the events which are at least three days long for vertical lines
        $where = "WHERE (MONTH(startdatum) = " . $month . " AND YEAR(startdatum) = " . $year . " AND (TO_DAYS(enddatum) - TO_DAYS(startdatum)) > 1 OR MONTH(enddatum) = " . $month . " AND YEAR(enddatum) = " . $year . " AND (TO_DAYS(enddatum) - TO_DAYS(startdatum)) > 2 OR startdatum < '" . $year . "-" . $month . "-01' AND enddatum > '" . $year . "-" . ($month + 1) . "-01' AND (TO_DAYS(enddatum) - TO_DAYS(startdatum)) > 2)";

        if (sizeof($useEventGroups) > 0) {
            $where .= " AND `$db_group_field` IN ('" . implode("','", $useEventGroups) . "') ";
        } else {
            $where .= ' AND 1=2 ';
        }

        //Add kota filter
        if ($module == 'daten') {
            $kota_where = kota_apply_filter('ko_event');
        } else {
            if ($module == 'reservation') {
                $kota_where = kota_apply_filter('ko_reservation');
            }
        }
        if ($kota_where != '') {
            $where .= " AND ($kota_where) ";
        }

        $order = "ORDER BY startdatum ASC, $db_group_field ASC";
        $eventArr = db_select_data($db_table, $where, "*,(TO_DAYS(enddatum) - TO_DAYS(startdatum)) AS duration ",
            $order);
        ko_set_event_color($eventArr);

        $columnFillArr = array();

        //draw the multiple day events
        // find the startday
        foreach ($eventArr as $currEvent) {
            if ($currEvent['duration'] <= 0) {
                continue;
            }

            ko_add_color_legend_entry($legend, $currEvent, $egs[$currEvent[$db_group_field]]);

            $endDay = (int)substr($currEvent['enddatum'], 8, 2);
            $duration = $currEvent['duration'];
            $eventStart = intval(str_replace('-', '', $currEvent['startdatum']));
            $eventEnd = intval(str_replace('-', '', $currEvent['enddatum']));
            if ((int)substr($currEvent['startdatum'], 5, 2) != $month) {
                $startDay = 1;
                $durationActMonth = $endDay;
            } else {
                $startDay = (int)substr($currEvent['startdatum'], 8, 2);
                $durationActMonth = $duration;
            }
            $durationActMonth = $endDay;
            //Find first free column to fit whole event into
            $useColumn = false;
            for ($column = 1; $column <= $maxMultiDayColumns; $column++) {
                $stop = false;

                for ($dayCounter = $startDay; $dayCounter <= $startDay + $durationActMonth; $dayCounter++) {
                    if (isset($columnFillArr[$dayCounter][$column])) {
                        $stop = true;
                    }
                }
                if ($useColumn === false && !$stop) {
                    $useColumn = $column;
                }
            }
            $sPos = $monthWidth - $useColumn * $stripeWidth;


            //Start and end outside of current month - full month
            if ($eventStart < intval($year . $month . '01') && $eventEnd > intval($year . $month . $numDays)) {
                $eventStartDay = 1;
                $eventStopDay = $numDays;
                $roundedCorners = '';
            } // event starts a month before, ends in this month
            else {
                if ($eventStart < intval($year . $month . '01')) {
                    $eventStartDay = 1;
                    $eventStopDay = $endDay;
                    $roundedCorners = '34';
                } // event starts in this month, ends next month
                else {
                    if ($duration > $numDays - $startDay) {
                        $eventStartDay = $startDay;
                        $eventStopDay = $numDays;
                        $roundedCorners = '12';
                    } // event starts and ends in this month
                    else {
                        $eventStartDay = $startDay;
                        $eventStopDay = $endDay;
                        $roundedCorners = '1234';
                    }
                }
            }
            $y = $top + ($offsetDays + $eventStartDay - 1) * $dayHeight;
            $height = ($eventStopDay - $eventStartDay + 1) * $dayHeight;

            //Get color from event group
            $hex_color = $currEvent['eventgruppen_farbe'];
            if (!$hex_color) {
                $hex_color = $egs[$currEvent[$db_group_field]]['farbe'];
            }
            if (!$hex_color) {
                $hex_color = 'aaaaaa';
            }
            $pdf->SetFillColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                hexdec(substr($hex_color, 4, 2)));
            //Render event box
            $pdf->RoundedRect($left + $sPos, $y + 0.1, $stripeWidth, $height - 0.2, 1.2, $roundedCorners, 'F');


            //Prepare text for this event
            if ($module == 'daten') {
                $titles = ko_daten_get_event_title($currEvent, $egs[$currEvent[$db_group_field]], $title_mode);
                $text = ko_get_userpref($_SESSION['ses_userid'],
                    'daten_pdf_use_shortname') ? $titles['short'] : $titles['text'];
                $shortText = $titles['short'];
            } else {
                $titles = ko_reservation_get_title($currEvent, $egs[$currEvent[$db_group_field]], $title_mode);
                $text = $titles['text'];
                $shortText = $titles['short'];
            }

            //Render vertical text
            $pdf->SetFont('fontn', '', 6);
            $hex_color = ko_get_contrast_color($hex_color, '000000', 'ffffff');
            if (!$hex_color) {
                $hex_color = '000000';
            }
            $pdf->SetTextColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                hexdec(substr($hex_color, 4, 2)));

            //Use shortText if text is too long
            if ($pdf->GetStringWidth($text) > $height && $shortText != '') {
                $text = $shortText;
            }
            //Shorten text so it'll fit
            $textLength = $pdf->GetStringWidth($text);
            while ($textLength > $height) {
                $text = substr($text, 0, -1);
                $textLength = $pdf->GetStringWidth($text);
            }
            $pdf->TextWithDirection($left + $sPos + 2, $y + $height / 2 + ($textLength / 2), $text, $direction = 'U');

            //mark column as used for the just rendered days
            for ($dayCounter = $eventStartDay; $dayCounter <= $eventStopDay; $dayCounter++) {
                $columnFillArr[$dayCounter][$useColumn] = 1;
            }
        }


        //get the events which are shorter than 3 days to draw single day events
        $where = "WHERE (MONTH(startdatum) = " . $month . " AND YEAR(startdatum) = " . $year . " AND (TO_DAYS(enddatum) - TO_DAYS(startdatum)) < 2 OR MONTH(startdatum) <> MONTH(enddatum) AND MONTH(enddatum) = " . $month . " AND YEAR(startdatum) = " . $year . " AND (TO_DAYS(enddatum) - TO_DAYS(startdatum)) < 2) ";
        if (sizeof($useEventGroups) > 0) {
            $where .= " AND `$db_group_field` IN ('" . implode("','", $useEventGroups) . "') ";
        } else {
            $where .= ' AND 1=2 ';
        }

        //Add kota filter
        if ($module == 'daten') {
            $kota_where = kota_apply_filter('ko_event');
        } else {
            if ($module == 'reservation') {
                $kota_where = kota_apply_filter('ko_reservation');
            }
        }
        if ($kota_where != '') {
            $where .= " AND ($kota_where) ";
        }

        $order = " ORDER BY startdatum ASC, startzeit ASC";
        $singleEventArr = db_select_data($db_table, $where, "*, (TO_DAYS(enddatum) - TO_DAYS(startdatum)) AS duration ",
            $order);
        ko_set_event_color($singleEventArr);


        //Count number of events for each day
        $eventsByDay = array();
        $events = array();
        foreach ($singleEventArr as $event) {
            //Add start date
            $dayNum = (int)substr($event['startdatum'], 8, 2);
            //Add end date if different from start date (2-day event)
            $dayNum2 = (int)substr($event['enddatum'], 8, 2);

            //Two-days event: Make two single entries
            if ($dayNum2 != $dayNum) {
                //Copy current event into two events
                $event1 = $event2 = $event;
                $event1['enddatum'] = $event1['startdatum'];
                $event2['startdatum'] = $event2['enddatum'];
                //If start and stop date are in the same month, then draw both this time
                if ((int)substr($event['startdatum'], 5, 2) == (int)substr($event['enddatum'], 5, 2)) {
                    $events[] = $event1;
                    $events[] = $event2;
                    $eventsByDay[$dayNum] += 1;
                    $eventsByDay[$dayNum2] += 1;
                } //If start and stop are in different months, only draw the one in the current month
                else {
                    if ((int)substr($event1['enddatum'], 5, 2) == $month) {
                        $events[] = $event1;
                        $eventsByDay[$dayNum] += 1;
                    }
                    if ((int)substr($event2['enddatum'], 5, 2) == $month) {
                        $events[] = $event2;
                        $eventsByDay[$dayNum2] += 1;
                    }
                }
            } //One-day event
            else {
                $events[] = $event;
                $eventsByDay[$dayNum] += 1;
            }
        }

        $eventCounterByDay = array();
        foreach ($events as $event) {
            ko_add_color_legend_entry($legend, $event, $egs[$event[$db_group_field]]);

            $startDay = (int)substr($event['startdatum'], 8, 2);
            $duration = $event['duration'];
            $eventStart = intval(str_replace('-', '', $event['startdatum']));

            //Increment counter for rendered events for this day
            $eventCounterByDay[$startDay] += 1;

            //Get upper half. Amount of events to be drawn in upper half of this day's box
            $half = ceil($eventsByDay[$startDay] / 2);

            //Calculate y coordinate for this event
            $y = $top + ($offsetDays + $startDay - 1) * $dayHeight;
            $y += $eventCounterByDay[$startDay] > $half ? $dayHeight / 2 : 0;

            //Set eventHeight and radius depending on number of events on this day
            $fullHeight = false;
            if ($eventsByDay[$startDay] > 1) {  //More than one event for this day
                $eventHeight = $dayHeight / 2;
                $radius = 0.6;
            } else {  //Only one event
                if ($event['startzeit'] == '00:00:00' && $event['endzeit'] == '00:00:00') {  //All day event fill the whole height
                    $eventHeight = $dayHeight;
                    $radius = 1;
                    $fullHeight = true;
                } else {  //Other events only fill half
                    $eventHeight = $dayHeight / 2;
                    $radius = 0.6;
                    if ((int)substr($event['startzeit'], 0, 2) > 12) {
                        $y += $dayHeight / 2;
                    }
                }
            }

            //Width available to render all events (depending on number of columns used by multi day events)
            $maxCol = max(array_keys($columnFillArr[$startDay]));
            $availableWidth = $monthWidth - 3 - $maxCol * $stripeWidth;

            //Set margin from the left
            $marginLeft = 3;

            //Calculate eventWidth and x coordinate
            if ($eventCounterByDay[$startDay] > $half) {
                $eventWidth = $availableWidth / ($eventsByDay[$startDay] - $half);
                $x = $left + $marginLeft + ($eventCounterByDay[$startDay] - $half - 1) * $eventWidth;
            } else {
                $eventWidth = $availableWidth / $half;
                $x = $left + $marginLeft + ($eventCounterByDay[$startDay] - 1) * $eventWidth;
            }


            //Add a little border around each event's box
            $eventWidth -= 0.2;
            $x += 0.1;
            $y += 0.1;
            $eventHeight -= 0.2;

            //Get color from event group
            $hex_color = $event['eventgruppen_farbe'];
            if (!$hex_color) {
                $hex_color = $egs[$event[$db_group_field]]['farbe'];
            }
            if (!$hex_color) {
                $hex_color = 'aaaaaa';
            }
            $pdf->SetFillColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                hexdec(substr($hex_color, 4, 2)));
            //Render event box
            $pdf->RoundedRect($x, $y, $eventWidth, $eventHeight, $radius, '234', 'F');

            //Prepare text for this event
            if ($module == 'daten') {
                $titles = ko_daten_get_event_title($event, $egs[$event[$db_group_field]], $title_mode);
                $text = ko_get_userpref($_SESSION['ses_userid'],
                    'daten_pdf_use_shortname') ? $titles['short'] : $titles['text'];
                $shortText = $titles['short'];
            } else {
                $titles = ko_reservation_get_title($event, $egs[$event[$db_group_field]], $title_mode);
                $text = $titles['text'];
                $shortText = $titles['short'];
            }


            //Prepare text
            $pdf->SetFont('fontn', '', 6);
            $hex_color = ko_get_contrast_color($hex_color, '000000', 'ffffff');
            if (!$hex_color) {
                $hex_color = '000000';
            }
            $pdf->SetTextColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
                hexdec(substr($hex_color, 4, 2)));
            $textPos = $y + 1.8;
            $textPos += $fullHeight ? $eventHeight / 4 : 0;


            //Use shortText if text is too long
            if ($pdf->GetStringWidth($text) > $eventWidth && $shortText != '') {
                $text = $shortText;
            }
            //Shorten text so it'll fit
            $textLength = $pdf->GetStringWidth($text);
            while ($textLength > $eventWidth && strlen($text) > 0) {
                $text = substr($text, 0, -1);
                $textLength = $pdf->GetStringWidth($text);
            }
            $pdf->Text($x + 0.1, $textPos, $text);

        }//foreach(events as event)


        //Add week numbers
        if ($showWeekNumbers) {
            $pos = $top + $offsetDays * $dayHeight;
            $pdf->SetFont('fontn', '', 5);
            for ($i = 1; $i <= $numDays; $i++) {
                if (substr(strftime('%u', mktime(1, 1, 1, $month, $i, $year)), 0, 2) == 1) {
                    $pdf->SetTextColor(150);
                    $pdf->SetFillColor(255, 255, 255);
                    $pdf->Circle($left + 3.7, $pos + 0.1, 1.15, 'F');
                    $kw = (int)date('W', mktime(1, 1, 1, $month, $i, $year));
                    $pdf->Text($left + 3.7 - ($pdf->GetStringWidth($kw) / 2), $pos + 0.8, $kw);
                }
                $pos = $pos + $dayHeight;
            }
        }//if(showWeekNumbers)

        $left += $monthWidth;
    }


    //Add legend (only for two or more entries and userpref)
    if ($show_legend && sizeof($legend) > 1) {
        $right = $planSize * $monthWidth + 5;
        ko_cal_export_legend($pdf, $legend, ($top - 12.5), $right);
    }


    $filename = $filename_prefix . strftime("%d%m%Y_%H%M%S", time()) . ".pdf";
    $file = $BASE_PATH . "download/pdf/" . $filename;
    $ret = $pdf->Output($file);

    return 'download/pdf/' . $filename;
}//ko_export_cal_pdf_year()


function ko_cal_export_legend(&$pdf, $legend, $top, $right)
{
    if (!is_array($legend) || sizeof($legend) < 2) {
        return;
    }

    //Number of entries per column
    $perCol = 3;

    $fontSize = 6;
    $boxSize = $fontSize / 2;
    $y = $top;

    //Sort legends by length of title for maximum space usage
    $sort = array();
    foreach ($legend as $title => $color) {
        $sort[$title] = strlen($title);
    }
    asort($sort);
    $new = array();
    foreach ($sort as $k => $v) {
        $new[$k] = $legend[$k];
    }
    $legend = $new;

    //Find max width of legend titles
    $widths = array();
    $colCounter = 0;
    $pdf->SetFont('fontn', '', $fontSize);
    $counter = 0;
    foreach ($legend as $title => $color) {
        $widths[$colCounter] = max($widths[$colCounter], $pdf->GetStringWidth($title));
        $counter++;
        if (fmod($counter, $perCol) == 0) {
            $colCounter++;
            $widths[$colCounter] = 0;
        }
    }
    foreach ($widths as $k => $v) {
        $widths[$k] = $v + 2;
    }

    $count = 0;
    $colCounter = 0;
    $x = $right - $widths[0];
    foreach ($legend as $title => $color) {
        $hex_color = ko_get_contrast_color($color, '000000', 'ffffff');
        if (!$hex_color) {
            $hex_color = '000000';
        }
        $pdf->SetTextColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
            hexdec(substr($hex_color, 4, 2)));

        $hex_color = $color;
        if (!$hex_color) {
            $hex_color = 'aaaaaa';
        }
        $pdf->SetFillColor(hexdec(substr($hex_color, 0, 2)), hexdec(substr($hex_color, 2, 2)),
            hexdec(substr($hex_color, 4, 2)));
        $pdf->SetDrawColor(255);

        $pdf->Rect($x, $y, $widths[$colCounter], $boxSize, 'FD');
        $pdf->Text($x + 1, $y + 0.75 * $boxSize, $title);

        $count++;
        if (fmod($count, $perCol) == 0) {
            $colCounter++;
            $x -= $widths[$colCounter];
            $y = $top;
        } else {
            $y += $boxSize;
        }
    }
}//ko_cal_export_legend()


function ko_add_color_legend_entry(&$legend, $event, $item)
{
    global $EVENT_COLOR;

    $key = $value = '';
    if (is_array($EVENT_COLOR) && sizeof($EVENT_COLOR) > 0 && $event[$EVENT_COLOR['field']] && $EVENT_COLOR['map'][$event[$EVENT_COLOR['field']]]) {
        $key = $event[$EVENT_COLOR['field']];
        $value = $EVENT_COLOR['map'][$event[$EVENT_COLOR['field']]];
    } else {
        $key = $item['name'];
        $value = $item['farbe'];
    }
    if (!$value) {
        $value = 'aaaaaa';
    }

    if ($key) {
        $legend[$key] = $value;
    }
}//ko_add_color_legend_entry()


/**
 * Generiert Personen-Liste gem�ss Einstellungen (Familie, Personen oder gem�ss "AlsFamilieExportieren")
 */
function ko_generate_export_list($personen, $familien, $mode)
{
    if ($mode == "p") {
        return array(implode(",", $personen), "");
    } else {
        if ($mode == "f" || $mode == "def") {
            if (is_array($personen)) {
                foreach ($personen as $pid) {
                    if ($pid) {
                        ko_get_person_by_id(format_userinput($pid, "uint"), $p);
                        if ($p["famid"] > 0) {
                            $f = ko_get_familie($p["famid"]);
                            if ($mode == "f" || ($f["famgembrief"] == "ja" || !isset($f["famgembrief"]))) {
                                $fam[] = $p["famid"];
                            } else {
                                $person[] = $p["id"];
                            }
                        } else {
                            $person[] = format_userinput($pid, "uint");
                        }
                    }//if(pid)
                }//foreach(personen as pid)
                $xls_auswahl = implode(",", $person);
            } else {
                $xls_auswahl = "";
            }

            if (is_array($familien)) {
                foreach ($familien as $f) {
                    if ($f) {
                        $fam[] = format_userinput($f, "uint");
                    }
                }
            }
            $xls_fam_auswahl = is_array($fam) ? implode(",", array_unique($fam)) : "";
        }
    }//if(mode == f)

    return array($xls_auswahl, $xls_fam_auswahl);
}//ko_generate_export_list()


function ko_export_etiketten(
    $_vorlage,
    $_start,
    $_rahmen,
    $data,
    $fill_page = 0,
    $multiply = 1,
    $return_address = false,
    $return_address_mode = '',
    $return_address_text = ''
)
{
    global $BASE_PATH;

    ko_get_etiketten_vorlage(format_userinput($_vorlage, "js"), $vorlage);
    $start = format_userinput($_start, "uint");

    //Fill page if needed
    $fill_page = format_userinput($fill_page, "uint");
    if ($fill_page > 0) {
        $total = sizeof($data);
        $available = $fill_page * (int)$vorlage["per_col"] * (int)$vorlage["per_row"] - $start + 1;
        $new = $total;
        while ($new < $available) {
            $data[$new] = $data[(int)fmod($new, $total)];
            $new++;
        }
    }//if(fill_page)

    //Multiply entries
    $multiplyer = format_userinput($multiply, 'uint');
    if (!$multiplyer) {
        $multiplyer = 1;
    }
    if ($multiplyer > 1) {
        $orig = $data;
        unset($data);
        foreach ($orig as $address) {
            for ($i = 0; $i < $multiplyer; $i++) {
                $data[] = $address;
            }
        }
    }

    //Get fonts to be used
    $all_fonts = ko_get_pdf_fonts();
    $fonts = array('arial');
    if ($vorlage['font']) {
        $fonts[] = $vorlage['font'];
        $font = $vorlage['font'];
    } else {
        $font = 'arial';
    }
    if ($vorlage['ra_font']) {
        $fonts[] = $vorlage['ra_font'];
        $ra_font = $vorlage['ra_font'];
    } else {
        $ra_font = 'arial';
    }
    $fonts = array_unique($fonts);

    //Measures for possible page formats
    $formats = array(
        'A4' => array(210, 297),
        'A5' => array(148, 210),
        'A6' => array(105, 148),
        'C5' => array(162, 229),
    );
    if (!$vorlage['page_format'] || !in_array($vorlage['page_format'], array_keys($formats))) {
        $vorlage['page_format'] = 'A4';
    }
    if (!$vorlage['page_orientation'] || !in_array($vorlage['page_orientation'], array('L', 'P'))) {
        $vorlage['page_orientation'] = 'P';
    }

    //Set pageW and pageH according to preset
    list($pageW, $pageH) = $formats[$vorlage['page_format']];
    if ($vorlage['page_orientation'] == 'L') {
        $t = $pageW;
        $pageW = $pageH;
        $pageH = $t;
    }

    //PDF starten
    define('FPDF_FONTPATH', $BASE_PATH . 'fpdf/schriften/');
    require($BASE_PATH . 'fpdf/mc_table.php');
    $pdf = new PDF_MC_Table($vorlage['page_orientation'], 'mm', $formats[$vorlage['page_format']]);
    $pdf->Open();
    $pdf->SetAutoPageBreak(false);
    $pdf->calculateHeight(false);
    $pdf->SetMargins($vorlage["border_left"], $vorlage["border_top"], $vorlage["border_right"]);
    foreach ($fonts as $f) {
        $pdf->AddFont($f, '', $all_fonts[$f]['file']);
    }
    $pdf->AddPage();

    //Spaltenbreiten ausrechnen
    $page_width = $pageW - $vorlage["border_left"] - $vorlage["border_right"];
    $col_width = $page_width / $vorlage["per_row"];
    $cols = array();
    for ($i = 0; $i < $vorlage["per_row"]; $i++) {
        $cols[] = $col_width;
    }
    $pdf->SetWidths($cols);

    //Zeilenh�he
    $page_height = $pageH - $vorlage["border_top"] - $vorlage["border_bottom"];
    $row_height = $page_height / $vorlage["per_col"];
    $pdf->SetHeight($row_height);

    //Rahmen
    if ($_rahmen == "ja") {
        $pdf->border(true);
    } else {
        $pdf->border(false);
    }

    //Text-Ausrichtung
    for ($i = 0; $i < $vorlage["per_row"]; $i++) {
        $aligns[$i] = $vorlage["align_horiz"] ? $vorlage["align_horiz"] : "L";
    }
    for ($i = 0; $i < $vorlage['per_row']; $i++) {
        $valigns[$i] = $vorlage['align_vert'] ? $vorlage['align_vert'] : 'T';
        //Don't allow center align with return address, as this may lead to overlapping text
        if ($return_address && $valigns[$i] == 'C') {
            $valigns[$i] = 'T';
        }
    }

    //Prepare return address
    if ($return_address) {
        if (strstr($return_address_mode, 'manual_address') != false) {
            $ra = $return_address_text;
        } else {
            if (strstr($return_address_mode, 'login_address') != false) {
                $person = ko_get_logged_in_person();
                $ra = $person['vorname'] ? $person['vorname'] . ($person['nachname'] ? ' ' . $person['nachname'] : '') . ', ' : '';
                $ra .= $person['adresse'] ? $person['adresse'] . ', ' : '';
                $ra .= $person['plz'] ? $person['plz'] . ' ' : '';
                $ra .= $person['ort'] ? $person['ort'] . ', ' : '';
                if (substr($ra, -2) == ', ') {
                    $ra = substr($ra, 0, -2);
                }
            } else {
                $ra = ko_get_setting('info_name') ? ko_get_setting('info_name') . ', ' : '';
                $ra .= ko_get_setting('info_address') ? ko_get_setting('info_address') . ', ' : '';
                $ra .= ko_get_setting('info_zip') ? ko_get_setting('info_zip') . ' ' : '';
                $ra .= ko_get_setting('info_city') ? ko_get_setting('info_city') . ', ' : '';
                if (substr($ra, -2) == ', ') {
                    $ra = substr($ra, 0, -2);
                }
            }
        }
        if (strstr($return_address_mode, 'pp') != false) {
            $ra = getLL('leute_return_address_pp') . ' ' . $ra;
        }

        $ra_aligns = $ra_valigns = array();
        for ($c = 1; $c <= $vorlage['per_row']; $c++) {
            $ra_aligns[] = 'L';
            $ra_valigns[] = 'T';
        }
    }

    //Calculate image width
    if ($vorlage['pic_file'] && file_exists($BASE_PATH . $vorlage['pic_file'])) {
        $pic_w = $vorlage['pic_w'] ? $vorlage['pic_w'] : $col_width / 4;
        //Limit width of the picture to the width of one label
        if ($pic_w > $col_width) {
            $pic_width = $col_width;
        }
        //Limit x position so the picture doesn't leave the label
        if ($vorlage['pic_x'] + $pic_w > $col_width) {
            $vorlage['pic_x'] = $col_width - $pic_w;
        }
        //Limit y position so the picture doesn't leave the label
        $imagesize = getimagesize($BASE_PATH . $vorlage['pic_file']);
        $pic_h = $pic_w / $imagesize[0] * $imagesize[1];
        if ($vorlage['pic_y'] + $pic_h > $row_height) {
            $vorlage['pic_y'] = $row_height - $pic_h;
        }
    }

    //Etiketten schreiben
    $all_cols = sizeof($data);
    $last = false;
    $firstpage = true;
    $do_label = false;
    $done = 0;
    $page_counter = 0;
    while (!$last) {
        for ($r = 1; $r <= $vorlage["per_col"]; $r++) {  //�ber alle Zeilen
            $row = array();
            if ($return_address) {
                $ra_row = array();
            }
            $do_row = false;
            if (!$last) {
                for ($c = 1; $c <= $vorlage["per_row"]; $c++) {  //�ber alle Spalten
                    $cell_counter++;
                    if ($firstpage) {  //Auf erster Seite nach erster zu druckenden Etikette suchen
                        if ($cell_counter >= $start) {
                            $do_label = true;
                        }
                    }//if(firstpage)

                    if ($do_label) {
                        if ($done >= $all_cols) {
                            $last = true;
                        }
                        if (!$last) {
                            $row[] = $data[$done];
                            if ($return_address) {
                                $ra_row[] = $ra;
                            }
                            $do_row = true;
                            $done++;

                            //Add picture if one is given in the selected label preset
                            if ($vorlage['pic_file'] && file_exists($BASE_PATH . $vorlage['pic_file'])) {
                                $pic_x = $vorlage['border_left'] + ($c - 1) * $col_width + $vorlage['pic_x'];
                                $pic_y = $vorlage['border_top'] + ($r - 1) * $row_height + $vorlage['pic_y'];
                                $pdf->Image($BASE_PATH . $vorlage['pic_file'], $pic_x, $pic_y, $pic_w);
                            }
                        }//if(!last)
                    }//if(do_label)
                    else {
                        $row[] = ' ';
                        if ($return_address) {
                            $ra_row[] = ' ';
                        }
                    }

                }//for(c=1..vorlage[per_row])

                //Print return address on each label of this row
                if ($return_address && $do_row) {
                    //Store coordinates and line height
                    $save['x'] = $pdf->GetX();
                    $save['y'] = $pdf->GetY();
                    $save['zeilenhoehe'] = $pdf->zeilenhoehe;

                    //Print return address
                    $ra_margin_left = $vorlage['ra_margin_left'] != '' ? $vorlage['ra_margin_left'] : 3;
                    $ra_margin_top = $vorlage['ra_margin_top'] != '' ? $vorlage['ra_margin_top'] : 5;
                    $ra_textsize = $vorlage['ra_textsize'] ? $vorlage['ra_textsize'] : 8;

                    $pdf->SetFont($ra_font, '', $ra_textsize);
                    $pdf->SetZeilenhoehe(3.5);
                    $pdf->SetAligns($ra_aligns);
                    $pdf->SetvAligns($ra_valigns);
                    $pdf->SetInnerBorders($ra_margin_left, $ra_margin_top);
                    $pdf->Row($ra_row);
                    //Add a line beneath the return address
                    $lines = $pdf->NbLines($col_width - 2 * $ra_margin_left, $ra);
                    $line_top = $save['y'] + $ra_margin_top + 3.5 * $lines;
                    $pdf->Line($vorlage['border_left'], $line_top, $pageW - $vorlage['border_right'], $line_top);

                    //Restore coordinates and line height
                    $pdf->SetXY($save['x'], $save['y']);
                    $pdf->SetZeilenhoehe($save['zeilenhoehe']);
                } else {
                    $ra_margin_top = 0;
                }

                //Set aligns, font and border for actual address content
                $pdf->SetAligns($aligns);
                $pdf->SetvAligns($valigns);
                if ($return_address && $valigns[0] == 'T') {
                    $spacing_vert = max($vorlage['spacing_vert'], $line_top - $save['y'] + 2);
                } else {
                    $spacing_vert = $vorlage['spacing_vert'];
                }
                $pdf->SetInnerBorders($vorlage['spacing_horiz'], $spacing_vert);
                $pdf->SetFont($font, '', $vorlage["textsize"] ? $vorlage["textsize"] : 11);
                $pdf->SetZeilenhoehe(($vorlage['textsize'] ? $vorlage['textsize'] : 11) / 2);
                $pdf->Row($row);
            }//if(!last)
        }//for(r=1..vorlage[per_col])
        $page_counter++;
        $firstpage = false;
        if ($done < $all_cols) {
            $pdf->AddPage();
        }
        $cell_counter = 0;
    }//while(!$last)

    $filename = $BASE_PATH . "download/pdf/" . getLL("leute_labels_filename") . strftime("%d%m%Y_%H%M%S",
            time()) . ".pdf";
    $pdf->Output($filename);

    return "download/pdf/" . basename($filename);
}//ko_export_etiketten()


function ko_get_pdf_fonts()
{
    global $BASE_PATH;

    $fonts = array();
    $files_php = $files_z = array();

    $font_path = $BASE_PATH . "fpdf/schriften";
    if ($dh = opendir($font_path)) {
        while (($file = readdir($dh)) !== false) {
            if (substr($file, -2) == ".z") {
                $files_z[] = substr($file, 0, -2);
            } else {
                if (substr($file, -4) == ".php") {
                    $files_php[] = substr($file, 0, -4);
                }
            }
        }
        closedir($dh);
    }

    foreach ($files_z as $font) {
        if (!in_array($font, $files_php)) {
            continue;
        }
        $ll = getLL('fonts_' . $font);
        $fonts[$font] = array("file" => $font . ".php", "name" => ($ll ? $ll : $font), "id" => $font);
    }
    ksort($fonts, SORT_LOCALE_STRING);

    return $fonts;
}//ko_get_pdf_fonts()


/**
 * Try to find a pdf_logo file to be used in PDF exports
 */
function ko_get_pdf_logo()
{
    global $BASE_PATH;

    $r = '';
    $open = @opendir($BASE_PATH . 'my_images/');
    while ($file = @readdir($open)) {
        if (preg_match('/^pdf_logo\.(png|jpg|jpeg|gif)$/i', $file)) {
            $r = $file;
        }
    }

    return $r;
}//ko_get_pdf_logo()


/**
 * Checks whether LaTeX is installed and pdflatex may be called
 */
function ko_latex_check()
{
    global $PDFLATEX_PATH;

    exec($PDFLATEX_PATH . 'pdflatex -version', $ret);
    if (sizeof($ret) == 0) {
        return false;
    }
    if (false !== strpos($ret[0], 'TeX')) {
        return true;
    }
    return false;
}//ko_latex_check()


/**
 * Compiles a LaTeX file
 *
 * @param     $file Input file to be compiled
 * @returns   LaTeX compiler output
 */
function ko_latex_compile($file)
{
    global $BASE_PATH, $PDFLATEX_PATH;

    system('cd ' . $BASE_PATH . 'latex/compile/ && ' . $PDFLATEX_PATH . 'pdflatex -interaction nonstopmode ' . $file . '.tex 2&>/dev/null',
        $ret);
    return $ret;
}//ko_latex_compile()


/**
 * Get all LaTeX layouts stored in latex/layouts for the given type (*.lco files)
 */
function ko_latex_get_layouts($type)
{
    global $ko_path;

    $layouts = array();
    if ($handle = opendir($ko_path . 'latex/layouts/')) {
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..' || substr($file, -4) != '.lco' || substr($file, 0,
                    strlen($type)) != $type
            ) {
                continue;
            }
            $layouts[] = substr($file, strlen($type) + 1, -4);
        }
    }
    closedir($handle);

    sort($layouts, SORT_LOCALE_STRING);
    return $layouts;
}//ko_latex_get_layouts()


/**
 * Escape charachters from user input so they will show correctly in LaTeX
 */
function ko_latex_escape_chars($text)
{
    $map = array(
        '<' => '\textless{}',
        '>' => '\textgreater{}',
        '~' => '\textasciitilde{}',
        '^' => '\textasciicircum{}',
        '&' => '\&',
        '#' => '\#',
        '_' => '\_',
        '$' => '\$',
        '%' => '\%',
        '|' => '\docbooktolatexpipe{}',
        '{' => '\{',
        '}' => '\}',
        '"' => "''",
    );

    return strtr(stripslashes($text), $map);
}//ko_latex_escape_chars()


/**
 * Checks whether pdftk is installed. Is needed to merge several PDF files
 */
function ko_check_for_pdftk()
{
    exec('pdftk --version', $ret);
    if (sizeof($ret) == 0) {
        return false;
    }
    if (false !== strpos($ret[1], 'pdftk')) {
        return true;
    }
    return false;
}//ko_check_for_pdftk()

