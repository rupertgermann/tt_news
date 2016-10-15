/***************************************************************
 *
 *  javascript functions for the tt_news catmenu
 *  relies on the javascript library "prototype"
 *
 *
 *  Copyright notice
 *
 *  (c) 2006-2009	Benjamin Mack <www.xnos.org>
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
 * @author	Benjamin Mack
 * @author	Rupert Germann
 * 
 * modified for the tt_news FE category menu (catmenu) by Rupert Germann <rupi@gmx.li>
 *
 * $Id$
 *
 */
 
var categoryTree = {
	thisScript: 'index.php?eID=tt_news',
	ajaxID: 'tx_ttnews_catmenu::expandCollapse',
	frameSetModule: null,
	activateDragDrop: false,
	highlightClass: 'active',
//	recID: 0,

	// reloads a part of the page tree (useful when "expand" / "collapse")
	load: function(params, isExpand, obj, pid, cObjUid, L) {
			// fallback if AJAX is not possible (e.g. IE < 6)
		if (typeof Ajax.getTransport() != 'object') {
			window.location.href = this.thisScript + '?id=' + pid + '&PM=' + params + '&L=' + L;
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
			parameters: 'ajaxID=' + this.ajaxID + '&PM=' + params + '&id=' + pid + '&cObjUid=' + cObjUid + '&L=' + L,
			onComplete: function(xhr) {
				// the parent node needs to be overwritten, not the object
				$(obj.parentNode).replace(xhr.responseText);
				//this.registerDragDropHandlers();
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
	}

};



