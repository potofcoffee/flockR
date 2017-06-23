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

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<pre>';

class ApiConverter
{
    protected $translation;

    function convertSingleSourceFile($file)
    {
        $sourceFile = 'api/' . $file . '.php';
        echo "Processing file {$file}<br />";
        $key = str_replace('api.', '', $file);
        if (strpos($key, '.')) {
            $key = str_replace('.', '', ucwords(join('.', array_reverse(explode('.', $key))), '.'));
        }

        $rawSource = str_replace("\r\n", "\n", file_get_contents($sourceFile));
        $sourceLines = explode("\n",$rawSource);
        // find all functions
        //preg_match_all('/function\s+(.*)\(/', $rawSource, $functionNames);
        preg_match_all('/(function (\w+)\((?:\n|[^\{])*\))\n?\{/', $rawSource, $functionNames, PREG_SET_ORDER, 0);

        $functions = [];
        require_once($sourceFile);

        $classSource = '';
        $className = ucfirst($key);

        $classMethods = [];

        foreach ($functionNames as $functionData) {
            $function = trim($functionData[2]);
            if (($x = strpos($function, '(')) > 0) {
                echo "( in {$function} at position {$x}!<br />";
                $function = trim(substr($function, 0, $x));
            }

            if (function_exists($function)) {
                $newName = $this->underscoreToCamelCase(str_replace(['ko_', $key . '_'], ['', ''], $function), false);
                echo "Converting {$file}:{$function}() to {$className}::{$newName}()<br />";
                $this->translation[$className][$function] = $newName;
                $reflectionFunction = new ReflectionFunction($function);
                //echo $reflectionFunction;
                $functionSource = $reflectionFunction->getDocComment() . "\n";
                $functionSource .= $this->extractSourceLines($sourceLines, $reflectionFunction->getStartLine(), $reflectionFunction->getEndLine());
                $functionSource = str_replace('function ' . $function . '(', 'public static function ' . $newName . '(', $functionSource);

                $classMethods[] = [
                    'oldName' => $function,
                    'newName' => $newName,
                    'class' => $className,
                    'staticCall' => $className.'::'.$newName,
                    'definition' => $functionData[1],
                    'docComment' => $reflectionFunction->getDocComment(),
                ];

                $classSource .= $this->indent($functionSource) . "\n\n";
            } else {
                echo "<span style='color: red'>Function {$file}:{$function}() does not exist.</span><br />";
            }
        }


        $classSource = '<?php
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


namespace Peregrinus\\Flockr\\Legacy\\Api;

/**
 * @class ' . $className . '
 * @source Automatically converted from ' . basename($sourceFile) . ' 
 */
class ' . $className . ' {

' . $classSource . '

}
';


        return ['class' => $className, 'source' => $classSource, 'file' => $file, 'fullPath' => $sourceFile, 'methods' => $classMethods];
    }

    function underscoreToCamelCase($string, $capitalizeFirstCharacter = false)
    {
        $str = str_replace('_', '', ucwords($string, '_'));
        if (!$capitalizeFirstCharacter) {
            $str = lcfirst($str);
        }
        return $str;
    }

    function extractSourceLines($lines, $start, $end)
    {
        $output = [];
        for ($i = $start - 1; $i <= $end; $i++) {
            $output[] = $lines[$i];
        }
        return join("\n", $output);
    }

    function indent($text, $spaces = 4)
    {
        $indent = str_pad('', $spaces, ' ');
        $lines = explode("\n", $text);
        foreach ($lines as $key => $line) {
            $lines[$key] = $indent . $lines[$key];
        }
        return join("\n", $lines);
    }

    public function translateFunctionNames($source, $className)
    {
        foreach ($this->translation as $api => $functions) {
            $apiPrefix = ($className == $api) ? 'static::' : $api . '::';
            foreach ($functions as $oldName => $newName) {
                $source = str_replace($oldName . '(', $apiPrefix . $newName . '(', $source);
            }
        }
        return str_replace('static function static::', 'static function ', $source);
    }

    public function getApiWrapper($class) {
        $output = "<?php
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

use Peregrinus\\Flockr\\Legacy\\Api\\".$class['class'].";


";

        foreach ($class['methods'] as $method) {
            echo "Creating wrapper function for {$method['staticCall']}<br />";

            $newDefinition = str_replace('function '.$method['oldName'].'(', $method['staticCall'].'(', $method['definition']);
            // properly indent new function call
            if (strpos($newDefinition, "\n") !== false) {
                $newDefinition = str_replace("\n", '', $newDefinition);
            }
            // remove call-by-reference indicators from function call
            $newDefinition = str_replace('&$', '$', $newDefinition);
            // remove type hints from function call
            $parameters = preg_split('/[\t,](?=(?:[^(?:\'|\")]|(?:\'|\")[^(?:\'|\")]*(?:\'|\"))*$)/', substr($newDefinition, strpos($newDefinition, '(')+1, -1));
            foreach ($parameters as $pKey => $parameter) {
                if (preg_match('/(\w+\s+)\$/', $parameter)) {
                    $parameter = preg_replace('/(\w+\s+)\$/', '\$', $parameter);
                }
                $parameters[$pKey] = trim($parameter);
            }
            echo $newDefinition.'<br />';
            $newDefinition = $method['staticCall'].'('.join(', ', $parameters).')';
            if (strlen($newDefinition)> 60) {
                $newDefinition = $method['staticCall']."(\n    ".join(",\n    ", $parameters)."\n)";
            }
            echo $newDefinition.'<br />';



            if ($method['docComment']) {
                $comment = explode("\n", str_replace(' */', '', $method['docComment']));
            } else {
                $comment = ['/**'];
            }
            $comment[] = ' * @deprecated 1.0 - '.date('Y-m-d');
            $comment[] = ' *  This is a legacy kOOL api function and has been superceded by flockR\'s new class-based legacy API.';
            $comment[] = ' *  It has been automatically created as a wrapper for '.$method['staticCall'].'()';
            $comment[] = ' *  Use '.$method['staticCall'].'() instead, if you need this functionality.';
            $comment[] = ' * @see '.$method['staticCall'].'()';
            $comment[] = ' * @todo Convert all uses of '.$method['oldName'].'() to '.$method['staticCall'].'()';
            $comment[] = ' */';
            $comment = join("\n", $comment)."\n";


            $functionSource = $comment.$method['definition']."\n"
                ."{\n"
                ."    // call the new api function\n"
                ."    return {$newDefinition};\n"
                ."}\n\n\n";

            $output .= $functionSource;
        }
        return $output;
    }
}

$converter = new ApiConverter();

$classes = [];
foreach (glob('api/*.php') as $file) {
    $class = $converter->convertSingleSourceFile(pathinfo($file, PATHINFO_FILENAME));
    $classes[] = $class;
}

foreach ($classes as $class) {
    $class['source'] = $converter->translateFunctionNames($class['source'], $class['class']);
    // write to file
    $classFile = 'Temp/class.' . $class['class'] . '.php';
    echo "Writing {$classFile}<br />";
    file_put_contents($classFile, $class['source']);

    // write wrapper
    $wrapperFile = 'Temp/wrapper.' . lcfirst($class['class']) . '.php';
    echo "Writing api wrapper {$wrapperFile}<br />";
    file_put_contents($wrapperFile, $converter->getApiWrapper($class));
}
