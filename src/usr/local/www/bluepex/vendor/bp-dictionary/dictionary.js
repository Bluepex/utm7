setTimeout(() => { walk(document.body); }, 100);

// Walk the DOM to search all html strings
function walk(node) {
	var child, next;
	switch (node.nodeType) {
		case 1:  // Element
		case 9:  // Document
		case 11: // Document fragment
			child = node.firstChild;
			while (child) {
				next = child.nextSibling;
				walk(child);
				child = next;
			}
			break;
		case 3: // Text node
			substrKeywordsFrom(node);
			break;
	}
}

function substrKeywordsFrom(textNode) {
	textNode.nodeValue = textNode.nodeValue.replace(/(pfsense)/gi, "BluePexUTM");
	textNode.nodeValue = textNode.nodeValue.replace(/alias/g, "objeto");
	textNode.nodeValue = textNode.nodeValue.replace(/Alias/g, "Objeto");
}
