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


namespace Peregrinus\Flockr\Core\Renderer;


use Peregrinus\Flockr\Core\AbstractModule;
use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Legacy\LegacyModule;
use Mpdf\Mpdf;

class PDFRenderer extends AbstractRenderer
{

    protected $content;
    protected $mpdf;
    protected $templateName = '';
    protected $data = [];
    protected $pdfConfiguration = [];
    protected $fontConfiguration = [];

    /**
     * PDFRenderer constructor.
     * @param string $templateName Template name
     * @param array $pdfConfiguration Configuration array for Mpdf
     *     This is merged with the configuration provided in mpdf section the global Configuration/PDF.yaml file
     *     Folders are configured relative to the FLOCKR_basePath folder
     * @see Mpdf\Mpdf::__construct() for configuration options
     */
    public function __construct($templateName, $pdfConfiguration = [])
    {
        $this->setTemplateName($templateName);
        $globalConf = ConfigurationManager::getInstance()->getConfigurationSet('PDF');
        $pdfConfiguration = array_merge($pdfConfiguration, $globalConf['mpdf']);
        $this->setPdfConfiguration($pdfConfiguration);
        $this->setFontConfiguration($globalConf['fonts']);
        $this->fixFolders('fontDir', 'vendor/mpdf/mpdf/ttfonts/');
    }

    /**
     * Fix folder configuration by prefixing paths with FLOCKR_basePath
     * @param string $key Folder configuration to fix
     * @param string $defaultFolder Optional: Include this default folder
     */
    protected function fixFolders($key, $defaultFolder = '') {
        foreach ($this->pdfConfiguration[$key] as $folderKey => $fontDir) {
            $this->pdfConfiguration[$key][$folderKey] = FLOCKR_basePath.$fontDir;
        }
        if ($defaultFolder) {
            $this->pdfConfiguration[$key] = array_merge([FLOCKR_basePath.$defaultFolder], $this->pdfConfiguration[$key]);
        }
    }

    /**
     * Assign data to the view template
     * @param string $key Key
     * @param mixed $value Value
     * @see TYPO3\Fluid\TemplateView::assign()
     */
    public function assign($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Render the template and write it to a PDF file
     * @param string $fileName Name of the rendered PDF file
     * @param string $moduleName The name of the legacy kOOL module (i.e. 'rota') calling this renderer
     * @param AbstractModule $module FLOCKR module responsible for this rendering (default: LegacyModule)
     * @param string $controllerName Optional controller name, default: PDF
     * @param string $action Optional action, default: ''
     * @param array $data Optional: Data to be assigned to the view before rendering
     *     Data is merged into the existing data set with PDFRenderer::assign()
     * @see PDFRenderer::assign()
     * @return mixed
     */
    public function render($fileName, $moduleName='', $module = null, $controllerName = 'PDF', $action= '', $data = []) {
        if (is_null($module)) $module = LegacyModule::getInstance();
        $actionName = lcfirst(pathinfo($fileName, PATHINFO_FILENAME));
        if ($moduleName) {
            $view = App::getInstance()->createView($module, $controllerName, $actionName, ucfirst($moduleName).'/Export/');
        } else {
            $view = App::getInstance()->createView($module, $controllerName, $actionName);
        }

        $view->getRenderingContext()->setControllerName($controllerName);
        $data = array_merge_recursive($data, $this->data);
        foreach ($data as $key => $value) {
            $view->assign($key, $value);
        }
        $view->assign('content', $this->content);

        $pdf = new Mpdf($this->pdfConfiguration);
        $pdf->allow_charset_conversion=true;
        $pdf->charset_in='UTF-8';

        // merge font configuration:
//        $pdf->fontdata = array_merge ($this->fontConfiguration, $pdf->fontdata);
        $pdf->writeHTML($view->render($this->templateName));
        $pdf->Output($fileName);
        return $fileName;
    }


    /**
     * @return string Content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Add to content
     * @param string $content
     */
    public function addContent($content) {
        $this->content .= $content;
    }


    /**
     * @return string
     */
    public function getTemplateName()
    {
        return $this->templateName;
    }

    /**
     * @param string $templateName
     */
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * @return array
     */
    public function getPdfConfiguration()
    {
        return $this->pdfConfiguration;
    }

    /**
     * @param array $pdfConfiguration
     */
    public function setPdfConfiguration($pdfConfiguration)
    {
        $this->pdfConfiguration = $pdfConfiguration;
    }

    /**
     * @return array
     */
    public function getFontConfiguration()
    {
        return $this->fontConfiguration;
    }

    /**
     * @param array $fontConfiguration
     */
    public function setFontConfiguration($fontConfiguration)
    {
        $this->fontConfiguration = $fontConfiguration;
    }



}