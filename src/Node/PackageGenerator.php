<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Node;


use Ceive\View\Layer\Transpiler\FS\FSGlob;

class PackageGenerator{
	
	public $dirname;
	
	/** @var  Package */
	protected $package;
	
	/** @var  Babel */
	protected $babel;
	
	
	public function __construct($dirname, $config = []){
		$this->dirname = $dirname;
		
		$this->config = array_replace([
			
			'appRoot'   => null,
			'webDir'    => '/public/',
			
			'src'       => 'src',
			'entry'     => 'src/main.js',
			
			'dist'      => 'dist',
			'distJs'    => 'bundle.js',
			'distCss'   => 'bundle.css',
		
			'webpackConfigName' => 'webpack.config.js'
		], $config);
	}
	
	public function checkExists(){
		
		if(!file_exists(FSGlob::p($this->dirname, 'package.json'))){
			return false;
		}
		
		if(!is_dir(FSGlob::p($this->dirname, 'node_modules'))){
			return false;
		}
		
		if(!glob(FSGlob::p($this->dirname, 'node_modules','*'))){
			return false;
		}
		
		if(!file_exists(FSGlob::p($this->dirname, 'webpack.config.json'))){
			return false;
		}
		
		if(!file_exists(FSGlob::p($this->dirname, '.babelrc'))){
			return false;
		}
		
		return true;
	}
	
	public function generate(){
		$dirname = $this->dirname;
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
		
		$package->script('build', 'webpack --mode development');
		
		$package->build();
		
		$babel = new Babel($dirname);
		$babel->preset("env", "react");
		$babel->plugin("transform-class-properties", ["spec" => true ]); // ES6 Class properties
		$babel->build();
		
		$dist = ltrim( FSGlob::cutBase(FSGlob::normalize($this->config['dist'],'/'), FSGlob::normalize($this->config['appRoot'],'/')),'/');
		$src = ltrim( FSGlob::cutBase(FSGlob::normalize($this->config['src'],'/'), FSGlob::normalize($this->config['appRoot'],'/')),'/');
		
		$webpackConfig = <<<JS
		
const path = require("path");
const dist = path.resolve(__dirname, "{$dist}");
const src =  path.resolve(__dirname, "{$src}");
const distJs = "{$this->config['distJs']}";
const webDir = "{$this->config['webDir']}";
		
module.exports = {
	context: src,
	entry: [
		path.resolve(src, "{$this->config['entry']}")
	],
	output: {
		path: dist,
		filename: distJs,
		publicPath: webDir,
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
		file_put_contents($dirname . '/'.$this->config['webpackConfigName'], $webpackConfig);
	}
	
	
	
	
	
}


