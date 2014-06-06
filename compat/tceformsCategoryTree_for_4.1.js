/***************************************************************
*
*  javascript functions regarding the page & folder tree
*  relies on the javascript library "prototype"
*
*
*  Copyright notice
*
*  (c) 2006-2008	Benjamin Mack <www.xnos.org>
*  All rights reserved
*
*  This script is part of the TYPO3 t3lib/ library provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in tslib/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
*
***************************************************************/

/**
 *
 * @author	Benjamin Mack
 * 
 * modified for the tt_news category menu by Rupert Germann <rupi@gmx.li>
 *
 * $Id: tceformsCategoryTree_for_4.1.js 18769 2009-04-07 13:09:47Z rupi $
 *
 */
 
var tceFormsCategoryTree = {
	thisScript: '../typo3conf/ext/tt_news/compat/be_axax_for_4.1.php',
	ajaxID: 'tceFormsCategoryTree::expandCollapse',
	frameSetModule: null,
	activateDragDrop: true,
	highlightClass: 'active',
//	recID: 0,

	// reloads a part of the page tree (useful when "expand" / "collapse")
	load: function(params, isExpand, obj, tceFormsTable, recID, storagePid) {
			// fallback if AJAX is not possible (e.g. IE < 6)
		if (typeof Ajax.getTransport() != 'object') {
			window.location.href = this.thisScript + '?ajaxID=' + this.ajaxID + '&PM=' + params;
			return;
		}

		// immediately collapse the subtree and change the plus to a minus when collapsing
		// without waiting for the response
		if (!isExpand) {
			var ul = obj.parentNode.getElementsByTagName('ul')[0];
			obj.parentNode.removeChild(ul); // no remove() directly because of IE 5.5
			var pm = Selector.findChildElements(obj.parentNode, ['.pm'])[0]; // Getting pm object by CSS selector (because document.getElementsByClassName() doesn't seem to work on Konqueror)
			if (pm) {
				pm.onclick = null;
				Element.cleanWhitespace(pm);
				pm.firstChild.src = pm.firstChild.src.replace('minus', 'plus');
			}
		} else {
			obj.style.cursor = 'wait';
		}

		new Ajax.Request(this.thisScript, {
			//method: 'get',
			parameters: 'ajaxID=' + this.ajaxID + '&PM=' + params + '&tceFormsTable=' + tceFormsTable + '&recID=' + recID  + '&storagePid=' + storagePid ,
			onComplete: function(xhr) {
				// the parent node needs to be overwritten, not the object
				$(obj.parentNode).replace(xhr.responseText);
				this.registerDragDropHandlers();
				//this.reSelectActiveItem();
				//filter($('_livesearch').value);
			}.bind(this),
			onT3Error: function(xhr) {
				// if this is not a valid ajax response, the whole page gets refreshed
				this.refresh();
			}.bind(this)
		});
	},

	// does the complete page refresh (previously known as "_refresh_nav()")
	refresh: function() {
		var r = new Date();
		// randNum is useful so pagetree does not get cached in browser cache when refreshing
		var search = window.location.search.replace(/&randNum=\d+/, '');
		window.location.search = search+'&randNum=' + r.getTime();
	},
	
	// attaches the events to the elements needed for the drag and drop (for the titles and the icons)
	registerDragDropHandlers: function() {
		if (!this.activateDragDrop) return;
		this._registerDragDropHandlers('dragTitle');
		this._registerDragDropHandlers('dragIcon');
	},

	_registerDragDropHandlers: function(className) {
		var elements = Selector.findChildElements($('tree'), ['.'+className]); // using Selector because document.getElementsByClassName() doesn't seem to work on Konqueror
		for (var i = 0; i < elements.length; i++) {
			Event.observe(elements[i], 'mousedown', function(event) { DragDrop.dragElement(event); }, true);
			Event.observe(elements[i], 'dragstart', function(event) { DragDrop.dragElement(event); }, false);
			Event.observe(elements[i], 'mouseup',   function(event) { DragDrop.dropElement(event); }, false);
		}
	}

};



