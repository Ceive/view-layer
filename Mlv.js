
import React from 'react';

export class Holder{

	name;
	ownerBlock;

	default = null;
	targets = [];
	prepends = [];
	appends = [];

	_actualAttachedBlocks = null;

	elements;


	constructor(name, blockOwner = null){
		this.name = name;
		this.ownerBlock = blockOwner;
	}


	getPath(asArray = false){

		let path = [];

		if(this.ownerBlock instanceof DefaultBlock){
			let holder = this.ownerBlock.parentHolder;
			path = holder.getPath( true );
		}

		if(this.name){
			path.push(this.name);
		}

		return asArray? path: (path.join('.') || null);
	}


	getContents(){
		if(!this.contents){
			this.contents = [];

			this.getActualAttachedBlocks(true).map((block)=>{
				block.getContents().map((content)=>{
					this.contents.push(content);
				});
			});

		}
		return this.contents;
	}

	/**
	 * Вернет блоки в формате композиции, только те которые актульны для процессинга, а именно учитавая,
	 * что некоторые ранние добавленные блоки перекрыты последующим и самым последним по низходящей в очереди блоком типа define если конечно он есть.
	 * Нисходящая: Высокий уровень > Более низкие уровни
	 * Уровень: Определяется позицией Слоя обладателя у блока
	 * @return array|null [target, prepends: prepends[], appends: appends[]]
	 */
	getActualAttachedBlocks(returnAsStack = false){
		if(!this._actualAttachedBlocks){
			let elements = this.elements = [];

			let prepends       = [];
			let appends        = [];

			let targetIndex    = null;
			let target         = null;
			let definedIn      = null;
			let definedLevel   = 0;
			let levels, index;

			levels = this._prepareBlocks(this.targets);
			index = 0;
			for(let {block, level} of levels){
				if(!target){
					target = block;
					targetIndex = index;
				}
				if(block.type instanceof BlockTypeDefine){
					definedIn = block;
					definedLevel = level;
					break;
				}
				index++;
			}

			if(!definedIn){
				definedIn = target;
				if(targetIndex){
					definedLevel = levels[targetIndex].level;
				}
			}

			levels = this._prepareBlocks(this.prepends, 'desc');
			for(let {block, level} of levels){
				if(level >= definedLevel){
					prepends.push(block);
				}
			}

			levels = this._prepareBlocks(this.appends);
			for(let {block, level} of levels){
				if(level >= definedLevel){
					appends.push(block);
				}
			}


			this._actualAttachedBlocks = { target, prepends, appends };
		}

		if(returnAsStack){
			let {target, prepends, appends} = this._actualAttachedBlocks;

			return []
				.concat(...prepends)
				.concat(...(target?[target]:[]))
				.concat(...appends);
		}

		return this._actualAttachedBlocks;
	}

	getContainHolders(){
		let holders = {};
		this.getActualAttachedBlocks(true).map((block)=>{
			holders = Object.assign(holders, block.getContainHolders());
		});
		return holders;
	}

	getContainHoldersBy(holderName = null){
		let holders = [];
		this.getActualAttachedBlocks(true).map((block)=>{
			holders = holders.concat(...block.getContainHoldersBy(holderName));
		});
		return holders;
	}

	reset(){
		this._actualAttachedBlocks = null;
		this.contents = null;
	}

	_prepareBlocks(blocks, $direction = 'asc'){

		if(!blocks || !blocks.length){
			return [];
		}


		let levels = [];
		for(let i=0; i< blocks.length ;i++){
			let block = blocks[i];
			let level = block.level;
			levels[i] = { block, level };
		}

		switch(true){
			case $direction === 'desc':

				levels.sort((a,b)=>{
					a = a.level;
					b = b.level;
					if(a===b){
						return 0;
					}
					return a>b? 1 : -1;
				});

				break;
			case $direction === 'asc':
			default:

				levels.sort((a,b)=>{
					a = a.level;
					b = b.level;
					if(a===b){
						return 0;
					}
					return a>b? -1 : 1;
				});

				break;
		}

		return levels;
	}


	addTarget(block){
		this.targets.push(block);

		this.reset();
	}
	removeTarget(block){
		let i =this.targets.indexOf(block);
		if(i>=0) this.targets.splice(i,1);
		this.reset();
	}

	addPrepend(block){
		this.prepends.push(block);
		this.reset();
	}
	removePrepend(block){
		let i =this.prepends.indexOf(block);
		if(i>=0) this.prepends.splice(i,1);
		this.reset();
	}

	addAppend(block){
		this.appends.push(block);
		this.reset();
	}
	removeAppend(block){
		let i =this.appends.indexOf(block);
		if(i>=0) this.appends.splice(i,1);
		this.reset();
	}


	registerDefaultBlock(type = null){
		if(!this.default){

			if(!type){
				type = BlockType.requireBlockType(BlockType.CASCADE);
			}else{
				type = this.ownerBlock.composition.layer.manager.requireType(type);
			}

			this.default = new DefaultBlock(type);
			this.default.name = this.getPath() || null;//path
			this.default.parentHolder = this;
			this.default.composition = this.ownerBlock.composition;

			type.attachToHolder(this.default, this);


		}

		return this.default;
	}

}


export class LayerManager{

	blockTypes = {};

	layers = {};

	constructor(){

		BlockType.registerDefaults(this);

	}

	registerLayer(source){
		let layer = null;
		if(!this.layers[source]){
			this.layers[source] = layer = new Layer();
			layer.source = source;
			layer.manager = this;
		}
		return this.layers[source];
	}

	requireLayer(source){
		if(!this.layers[source]){
			throw new Error(`Layer by source ${source} is not registered`);
		}
		return this.layers[source];
	}

	registerType(typeKey, blockType){
		this.blockTypes[typeKey] = blockType;
		return this;
	}

	requireType(typeKey){
		if(!this.blockTypes[typeKey]){
			throw new Error('Required type "'+typeKey+'" not registered in manager, please register a type or use other registered');
		}
		return this.blockTypes[typeKey];
	}

	keys;

	_chain;

	set chain(keys){

		this.unMountChain();

		let ancestor,layer;
		let requiredKeys = [];
		for(let key of keys){
			if(requiredKeys.indexOf(key)<0){
				requiredKeys.push(key);
				layer = this.requireLayer(key);
				layer.unpick();
				layer.ancestor = ancestor||null;
				ancestor = layer;
			}
		}
		this._chain = layer;
		this.keys = requiredKeys;
		this.onChainUpdate();
	}

	get chain(){
		return this._chain;
	}

	unMountChain(){
		if(this._chain){
			let l = this._chain, a;
			do{
				a = l.ancestor;
				l.ancestor = null;
				l.unpick();
			}while(l = a);
		}
		this._chain = null;
	}

	onChainUpdate(){}


}
export class Layer{

	static COMPOSITION_MAIN = ':main';

	manager = null;

	ancestor;

	compositions = {};

	scope = {};


	static compName(name){
		return name?name:Layer.COMPOSITION_MAIN;
	}

	get level(){
		if(this.ancestor){
			return this.ancestor.level + 1;
		}
		return 1;
	}

	constructor(ancestor = null){
		this.ancestor = ancestor;
	}


	pick(){
		for(let key of Object.getOwnPropertyNames(this.compositions)){
			let composition = this.compositions[key];
			composition.pick();
		}
	}

	unpick(){
		for(let key of Object.getOwnPropertyNames(this.compositions)){
			let composition = this.compositions[key];
			composition.unpick();
		}
	}

	/**
	 * @return $this
	 */
	pickChain(){
		this.pick();
		if(this.ancestor){
			this.ancestor.pickChain();
		}
		return this;
	}

	getContainHoldersBy(holderName = null){
		let holders = [];
		for(let cKey of Object.getOwnPropertyNames(this.compositions)){
			holders = holders.concat( ...(this.compositions[cKey].getContainHoldersBy(holderName)) );
		}

		return holders;
	}

	registerComposition(name){
		let _propKey = Layer.compName(name);
		if(!this.compositions[_propKey]){
			this.compositions[_propKey] = new Composition(name, this);
		}
		return this.compositions[_propKey];
	}

	requireComposition(name){
		let key = name===null?':main':name;
		if(!this.compositions[key]){
			throw new Error(`Composition by name ${key} not found`);
		}
		return this.compositions[key];
	}
	getComposition(name, delegateToAncestors = true){
		let _propKey = Layer.compName(name);
		let compositions = this.compositions;
		if(compositions[_propKey]){
			return compositions[_propKey];
		}
		if(!delegateToAncestors){
			return null;
		}
		return this.ancestor?this.ancestor.getComposition(name):null;
	}



	getCompositionTargetDefine(name = null, delegateToAncestors = true){
		let _propKey = Layer.compName(name);
		let compositions = this.compositions;
		let target;

		if(compositions[_propKey]){
			target = compositions[_propKey].target;
		}
		if(target && target.type instanceof BlockTypeDefine){
			return target;
		}

		if(!delegateToAncestors){
			return null;
		}
		let a = this.ancestor?this.ancestor.getCompositionTargetDefine(name, delegateToAncestors):null;
		if(!a && target){
			return target;
		}
		return a;
	}

	getContents(){
		this.pickChain();
		let define = this.getCompositionTargetDefine();
		return define.getContents();
	}

}

export class Composition{

	name = null;
	layer = null;

	target = null;
	appends = [];
	prepends = [];

	picked = false;

	constructor(name, layer){
		this.name = name;
		this.layer = layer;
	}

	registerBlock(type){

		if(!type){
			type = BlockType.requireBlockType(BlockType.TYPE_CASCADE)
		}else{
			type = this.layer.manager.requireType(type);
		}

		let block = new Block(type);
		block.name = this.name;

		type.attachToComposition(this, block);

		return block;
	}


	pick(){
		if(!this.picked){
			this.picked = true;

			for(let block of this.prepends){
				block.pick();
			}
			for(let block of this.appends){
				block.pick();
			}
			if(this.target){
				this.target.pick();
			}

		}
	}

	unpick(){
		if(this.picked){
			this.picked = false;

			for(let block of this.prepends){
				block.unpick();
			}
			for(let block of this.appends){
				block.unpick();
			}
			if(this.target){
				this.target.unpick();
			}

		}
	}

	getContainHoldersBy(holderName = null){
		let holders = [], searchName, compositionName = this.name;

		if(holderName && compositionName && holderName.substr(0, compositionName.length) === compositionName){
			searchName = holderName.substr(compositionName.length).replace(/^\.+|\.+$/g, '');
		}else{
			searchName = holderName;
		}

		if(searchName){
			searchName = searchName.replace(/^\.+|\.+$/g, '');
		}


		let block = this.target;
		if(block){
			if(compositionName === holderName){
				holders = holders.concat( ...(block.getContainHoldersBy(null) ) );
			}else{
				holders = holders.concat( ...(block.getContainHoldersBy(searchName) ) );
			}
		}
		for(let block of this.prepends){
			holders = holders.concat( ...(block.getContainHoldersBy(searchName) ) );
		}
		for(let block of this.appends){
			holders = holders.concat( ...(block.getContainHoldersBy(searchName) ) );
		}

		return holders;
	}

}

export class Block{

	type = null;

	name = null;

	composition = null;

	holdersRegistry = {};

	holders = [];

	holdsIn = [];

	get level(){
		return this.composition?this.composition.layer.level:null;
	}

	constructor(type){
		this.type = type;
	}

	registerHolder(name = null, identifierKey = null){
		if(identifierKey !== null && typeof identifierKey !== 'undefined'){
			identifierKey = String(identifierKey);
			if(!this.holdersRegistry[identifierKey]){
				this.holdersRegistry[identifierKey] = new Holder(name, this);

				this.holders.push(this.holdersRegistry[identifierKey]);

			}
			return this.holdersRegistry[identifierKey];
		}else{
			let holder = new Holder(name, this);
			this.holders.push(holder);
			return holder;
		}
	}

	pick(){

		let {name,layer} = this.composition;

		let holders = this._searchHoldersForPick(name, layer);

		for(let holder of holders){
			this.attachToHolder(holder);
		}

		return this;
	}

	unpick(){
		let holdsIn = this.holdsIn;
		this.holdsIn = [];

		for(let holder of holdsIn){
			this.detachFromHolder(holder);
		}

		return this;
	}

	attachToHolder(holder){
		this.holdsIn.push(holder);
		this.type.attachToHolder(this,holder);
	}

	detachFromHolder(holder){
		let i = this.holdsIn.indexOf(holder);
		if(i>=0){
			this.holdsIn.splice(i,1);
		}
		this.type.detachFromHolder(this,holder);
	}

	/**
	 *
	 * @param name
	 * @param layer
	 * @return Holder[]
	 * @private
	 */
	_searchHoldersForPick(name, layer){
		return this.type.searchHoldersForPick(this,layer);
	}

	getContainHolders(){
		return this.holders;
	}

	getContainHoldersBy(holderName = null){
		let targetHolders = [];
		for(let holder of this.holders){
			if(holder.getPath() === holderName){
				targetHolders.push(holder);
			}
			targetHolders = targetHolders.concat(...holder.getContainHoldersBy(holderName));
		}
		return targetHolders;
	}

	getContents(){
		/**
		 * Please redefine this method and return like jsx markup
		 * block.getContents = function(){
		 * 	return [
		 * 		<div></div>,
		 * 		<div></div>,
		 * 		<div></div>,
		 * 		<div></div>,
		 * 	];
		 * };
		 * block.getContents = block.getContents.bind( block );
		 *
		 * redefine example in /example.js
		 *
		 */
		throw new Error('Redefine method getContents in block!');
	}

}
export class DefaultBlock extends Block{

	parentHolder = null;

}



export class BlockType{

	static CASCADE = 'cascade';
	static REPLACE = 'replace';
	static DEFINE = 'define';
	static APPEND = 'append';
	static PREPEND = 'prepend';


	static defaultTypes = null;

	static getDefaultBlockTypes(){

		if(!BlockType.defaultTypes){
			BlockType.defaultTypes = {};

			BlockType.defaultTypes[BlockType.CASCADE] = new BlockTypeCascade();
			BlockType.defaultTypes[BlockType.REPLACE] = new BlockTypeReplace();
			BlockType.defaultTypes[BlockType.DEFINE] = new BlockTypeDefine();
			BlockType.defaultTypes[BlockType.PREPEND] = new BlockTypePrepend();
			BlockType.defaultTypes[BlockType.APPEND] = new BlockTypeAppend();

			for(let key of Object.getOwnPropertyNames(BlockType.defaultTypes)){
				let type = BlockType.defaultTypes[key];
				type.key = key;
			}

		}

		return BlockType.defaultTypes;
	}


	static registerDefaults(manager){
		let defaults = BlockType.getDefaultBlockTypes();
		for(let key of Object.getOwnPropertyNames(defaults)){
			manager.registerType(key, defaults[key]);
		}
	}

	static requireBlockType(typeKey){
		let defaults = BlockType.getDefaultBlockTypes();
		if(defaults[typeKey]){
			return defaults[typeKey];
		}
		throw new Error(`Required block type '${typeKey}' is not existing in default types`);
	}


	attachToHolder(block, holder){

	}

	detachFromHolder(block, holder){

	}

	searchHoldersForPick(block, layer){
		return [];
	}

	attachToComposition(composition, block){
		block.composition = composition;
	}
}

export class BlockTypeTarget extends BlockType{



	searchHoldersForPick(block, layer){
		let ancestor = layer.ancestor;
		let holders = [];
		while(ancestor){
			holders = ancestor.getContainHoldersBy(block.name);
			if(holders.length){
				break;
			}else{
				ancestor = ancestor.ancestor;
			}
		}
		return holders;
	}

	attachToHolder(block, holder){
		super.attachToHolder(block, holder);
		holder.addTarget(block);
	}

	detachFromHolder(block, holder){
		super.detachFromHolder(block, holder);
		holder.removeTarget(block);
	}

	attachToComposition(composition, block){
		super.attachToComposition(composition, block);
		composition.target = block;
	}

}

export class BlockTypeCoverer extends BlockType{

	searchHoldersForPick(block, layer){
		let ancestor = layer.ancestor;

		let holders;
		let ancestors = [];

		while(ancestor){
			ancestors.push(ancestor);
			ancestor = ancestor.ancestor;
		}
		ancestors = ancestors.reverse();

		for(let ancestor of ancestors){
			holders = ancestor.getContainHoldersBy(block.name);
			if(holders && holders.length) break;

		}

		return holders;
	}

}

export class BlockTypeDefine extends BlockTypeTarget{}
export class BlockTypeReplace extends BlockTypeTarget{}
export class BlockTypeCascade extends BlockTypeTarget{}
export class BlockTypeAppend extends BlockTypeCoverer{

	attachToHolder(block, holder){
		super.attachToHolder(block, holder);
		holder.addAppend(block);
	}

	detachFromHolder(block, holder){
		super.detachFromHolder(block, holder);
		holder.removeAppend(block);
	}
	attachToComposition(composition, block){
		super.attachToComposition(composition, block);
		composition.appends.push(block);
	}
}
export class BlockTypePrepend extends BlockTypeCoverer{
	attachToHolder(block, holder){
		super.attachToHolder(block, holder);
		holder.addPrepend(block);
	}

	detachFromHolder(block, holder){
		super.detachFromHolder(block, holder);
		holder.removePrepend(block);
	}
	attachToComposition(composition, block){
		super.attachToComposition(composition, block);
		composition.prepends.push(block);
	}
}


export function myCreateElement(type, attributes, ...children){

	let path = [];

	let holders = {};

	for(let key of Object.getOwnPropertyNames(attributes)){
		let value = attributes[key];

		if(value instanceof Holder){
			holders[key] = [value, path.concat( key ) ];
			//attributes[key] = value.getElements();
		}

	}

	if(attributes['ref']){

		attributes['ref'] = (element)=>{

			for(let key of Object.getOwnPropertyNames(holders)){
				let [holder, propertyLocation] = holders[key];
				holder.setComponent(element, propertyLocation);
			}

			if(attributes['ref']['call']){
				attributes['ref']();
			}

		}
	}else{

		attributes['ref'] = (element)=>{
			for(let key of Object.getOwnPropertyNames(holders)){
				let [holder, propertyLocation] = holders[key];
				holder.setComponent(element, propertyLocation);
			}
		}
	}

	return React.createElement(type, attributes, ...children);
}