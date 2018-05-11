<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

use Ceive\View\Layer\Node\Babel;
use Ceive\View\Layer\Node\Package;
use Ceive\View\Layer\Transpiler\FS\FSGlob;
use Ceive\View\Layer\Transpiler\Syntax;
use Ceive\View\Layer\Transpiler\Transpiler;
use PHPUnit\Framework\TestCase;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class SimpleCase
 * @package Ceive\View\Layer
 *
 * @TODO: Внедрить поддержку плейсхолдеров в шаблонах(блоках и макетах)
 * @TODO: Доступность аттрибутного контекста с локатором в шаблонах
 * @TODO: AC для текущего Слоя; общий AC; Attribute Context Aliasing
 *
 * layout => [
 *      lay: '*.tpl',
 *      tpl: '*.tpl',
 *      params: [
 *          'user' => '{operation.object}'
 *      ]
 * ]
 *
 *
 */
class SimpleCase extends TestCase{
	
	
	public function testNodeVerbose(){
		$dirname = dirname(__DIR__);
		chdir($dirname);
		
		/// Generate package.json
		
		$package = new Package($dirname);
		if(!$package->configLoaded){
			$package->initial(basename($dirname));
		}
		
		$package->devDependency('webpack');
		$package->devDependency('webpack-cli');
		
		$package->devDependency('babel-core');
		$package->devDependency('babel-loader');
		$package->devDependency('babel-preset-env');
		$package->devDependency('babel-preset-react');
		
		$package->devDependency('babel-plugin-transform-class-properties'); // ES6 Class properties
		
		$package->devDependency('url-loader'); // Url imports
		$package->devDependency('file-loader'); // File imports
		
		$package->dependency('react');
		$package->dependency('react-dom');
		
		$package->script('build', 'webpack --mode production');
		
		$package->build();
		
		$babel = new Babel($dirname);
		$babel->preset("env", "react");
		$babel->plugin("transform-class-properties", ["spec" => true ]); // ES6 Class properties
		$babel->build();
		
		
		
		$webpackConfig = <<<JS
		
const path = require("path");
const dist = path.resolve(__dirname, "view/build");
const src = path.resolve(__dirname, "view/dist");
		
		
module.exports = {
	context: src,
	entry: [
		path.resolve(__dirname, "view/dist/main.js")
	],
	output: {
		path: dist,
		filename: "bundle.js",
		publicPath: '/public/',
	},
	module: {

		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader"
				}
			},{
				test: /\.(png|jpg|)$/,
				loader: 'url-loader?limit=200000' // Url imports
			}
		]
	}
};
JS;
		file_put_contents($dirname . '/webpack.config.js', $webpackConfig);
		
		$package->npm('run build');
	}
	
	public function testLab(){
		
		
		
		
		$source = 'C:\OpenServer\domains\project.ru\src\layers\templates\a.tpl';
		$dest = 'C:\OpenServer\domains\project.ru\src\main.js';
		$result = $this->func($source, $dest);
		$this->assertEquals('../../main.js', $result);
		
		
		$source = 'C:\OpenServer\domains\project.ru\src\main.js';
		$dest = 'C:\OpenServer\domains\project.ru\src\layers\templates\a.tpl' ;
		$result = $this->func($source, $dest);
		$this->assertEquals('./layers/templates/a.tpl', $result);
		
		$source = 'C:\OpenServer\domains\project.ru\src\main.js';
		$dest = 'C:\OpenServer\domains\a\src\layers\templates\a.tpl' ;
		$result = $this->func($source, $dest);
		$this->assertEquals('../../a/src/layers/templates/a.tpl', $result);
		
		$source = 'src\main.js';
		$dest = 'src\layers\templates\a.tpl' ;
		$result = $this->func($source, $dest);
		$this->assertEquals('./layers/templates/a.tpl', $result);
		
		
		$source = 'src\layers\templates\a.tpl' ;
		$dest = 'src\main.js';
		$result = $this->func($source, $dest);
		$this->assertEquals('../../main.js', $result);
		
		$source = 'C:\op\src\layers\templates\a.tpl' ;
		$dest = 'C:\src\main.js';
		$result = $this->func($source, $dest, 'C:\op');
		$this->assertEquals(false, $result);
		
		
		$source = 'C:\sop\src\layers\templates\a.tpl' ;
		$dest = 'C:\src\main.js';
		$result = $this->func($source, $dest, 'C:\\');
		$this->assertEquals('../../../../src/main.js', $result);
		
	}
	
	public function func($src, $dest, $restrictBase = null){
		$srcDir = strtr( dirname($src), ['\\' => '/']);
		$destDir = strtr( dirname($dest), ['\\' => '/']);
		if($restrictBase)$restrictBase = strtr( $restrictBase, ['\\' => '/']);
		
		$equal = '';
		$cut = 0;
		
		$minLength = min(strlen($srcDir),strlen($destDir));
		
		for($i=0;($srcToken = @$srcDir{$i}) && ($destToken = @$destDir{$i}) && $srcToken === $destToken;$i++){
			$equal.=$srcToken;
			
			if($srcToken == '/'){
				$cut = 0;
			}else{
				$cut++;
			}
			
		}
		
		
		if($cut && $minLength > strlen($equal)){
			$equal = substr($equal, 0, -$cut);
		}
		
		if($restrictBase && substr($equal, 0, strlen($restrictBase)) !== $restrictBase){
			return false;
		}
		
		$srcSuffix = trim(substr($srcDir, strlen($equal)), '\/');
		$destSuffix = trim(substr($destDir, strlen($equal)), '\/');
		
		$path = $srcSuffix? array_fill( 0, count(explode('/',$srcSuffix)), '..'): null;
		$path = ($path?implode('/', $path):'.') . ($destSuffix? '/' . str_replace(['\\'],['/'],$destSuffix) :'') . '/' . basename($dest);
		
		return $path;
	}
	
	public function startWith($with, $in, &$resultEnding = null){
		$l = strlen($with);
		if(substr( $in, 0, $l) !== $with){
			return false;
		}
		$resultEnding = substr( $in, $l);
		return true;
	}
	
	public function testD(){
		
		$appDirname = dirname(__DIR__);
		
		$sourceDir  = FSGlob::path(null, [$appDirname, 'view/src']);
		$destDir    = FSGlob::path(null, [$appDirname, 'view/dist']);
		
		$transpiler                 = new Transpiler($sourceDir, $destDir);
		$transpiler->entryPoint     = 'index.js';
		$transpiler->layerManagerJs = 'layerManager.js';
		$transpiler->process(true);
		
	}
}


