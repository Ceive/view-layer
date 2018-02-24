<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

use Ceive\View\Layer\Block\BlockAppend;
use Ceive\View\Layer\Block\BlockCascade;
use Ceive\View\Layer\Block\BlockDefine;
use Ceive\View\Layer\Block\BlockPrepend;
use PHPUnit\Framework\TestCase;

class SimpleCase extends TestCase{
	
	public function testA(){
		
		
		$lay = new Lay([
			
			':main' => new Composition(new BlockDefine([
				
				(new Layout())
					->add(new BlockHolder('header'))
					->add(
						
						(new Layout())
							->add(new SimpleElement(' [ top ] '))
							->add(new BlockHolder(null))
							->add(new SimpleElement(' [ bottom ] '))
					
					)
					->add(new BlockHolder('footer'))
			
			]),null,null)
		
		]);
		
		$lay = new Lay([
			
			'header' => new Composition(new BlockCascade([
				
				(new Layout())
					->add(new BlockHolder(null))
					->add(new SimpleElement(' [ HEAD < ] '))
					->add(new BlockHolder('authorization'))
					->add(new SimpleElement(' [ HEAD > ] '))
			
			]),null,null),
			
			':main' => new Composition(new BlockCascade([
				
				(new Layout())->add(new SimpleElement(' [ cascade ] '))
			
			]),[
				new BlockPrepend([
					(new Layout())->add(new SimpleElement(' [ prepend ] '))
				])
			],[
				new BlockAppend([
					(new Layout())->add(new SimpleElement(' [ append ] '))
				])
			])
		
		], $lay);
		
		
		$lay = new Lay([
			
			'header.authorization' => new Composition(new BlockCascade([
				
				(new Layout())
					->add(new SimpleElement(' [ AUTHORIZATION ] '))
			
			]),null,null),
			
			
			'footer' => new Composition(new BlockCascade([
				
				(new Layout())->add(new SimpleElement(' [ footer-target ] '))
			
			]),[
				new BlockPrepend([ (new Layout())->add(new SimpleElement(' [ footer-prepend-L2 ] ')) ])
			],[
				new BlockAppend([ (new Layout())->add(new SimpleElement(' [ footer-append-L2 ] ')) ])
			])
		
		], $lay);
		
		$lay = new Lay([
			
			'header.authorization' => new Composition(new BlockCascade([
				
				(new Layout())
					->add(new SimpleElement(' [ AUTH_CASCADE ] '))
			
			]),null,null),
			
			'header' => new Composition(new BlockCascade([
				
				(new Layout())
					->add(new SimpleElement(' [ HEADER_CASCADE ] '))
			
			]),[
				new BlockPrepend([ (new Layout())->add(new SimpleElement(' [ header-prepend-L3 ] ')) ])
			],[
				new BlockAppend([ (new Layout())->add(new SimpleElement(' [ header-append-L3 ] ')) ])
			]),
			
			'footer' => new Composition(new BlockCascade([
				
				(new Layout())->add(new SimpleElement(' [ footer-target ] '))
			
			]),[
				new BlockPrepend([ (new Layout())->add(new SimpleElement(' [ footer-prepend-L3 ] ')) ])
			],[
				new BlockAppend([ (new Layout())->add(new SimpleElement(' [ footer-append-L3 ] ')) ])
			])
		
		], $lay);
		
		$level = $lay->getLevel();
		
		$layout = $lay->getMainLayout();
		$rendered = $layout->render();
		echo $rendered;
		
		
	}
	
	/**
	 *
	 */
	public function _old_testLay(){
		
		/* OLD
		$lay = new Lay(new DummyLayer([
			
			':main' => [
				new BlockReplace([
					new Layout([
						"Сайт Autist.com",
						"   {header}",
						"   --------------",
						"   {*}",
						"   --------------",
						"   {footer}",
						"   --------------",
						"   Контакты компании",
						"     Телефон: +99999",
						"     Email: question@autist.com",
					])
				])
			],
		    
		]));
		
		
		$lay = new Lay(new DummyLayer([
			
			':main' => [
				new BlockReplace([
					new Layout([
						"Раздел пользователи",
						"   {*}",
						"Корпорация рада всем нашим пользователям",
					])
				])
			],
			
			'header' => [
				new Block\BlockDefinition([
					new Layout([
						"Главная | Пользователи | Блог | Аккаунт | {authorization}",
					])
				])
			],
			
			'footer' => [
				new BlockReplace(['Ебать футер']),
			],
		
		]), $lay);
		
		
		
		$lay = new Lay(new DummyLayer([
			':main' => [
				new BlockReplace([
					new Layout([
						"Пользователь Вася",
						"   Пароль: JBGvwerb",
						"   Логин: Vasya",
						"   E-Mail: vasyka@mail.ru",
						"   Мобильный: +7 (914) 172 55 25 ",
					])
				])
			],
			
			'header' => [
				new Block\BlockAppend([
					new Layout([
						"Дополнительные элементы шапки",
					])
				])
			],
			
			'header.authorization' => [
				new Block\BlockDefinition([
					new Layout([
						"Авторизация | Регистрация | {*}",
					])
				])
			],
		
		
		]), $lay);
		
		$lay = new Lay(new DummyLayer([
			
			'header.authorization' => [
				new Block\BlockInner([
					new Layout([
						"После регистрации(вложенная магия)",
					])
				]),
				new Block\BlockAppend([
					new Layout([
						"Дополнительные элементы авторизации",
					])
				])
			],
			
			'footer' => [
				new Block\BlockPrepend(['Поставим перед футером']),
			],
		    
		]), $lay);
		
		echo $lay->render();
		*/
	}
	
}


