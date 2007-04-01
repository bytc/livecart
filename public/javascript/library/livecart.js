var LiveCart = {
    ajaxUpdaterInstance: null
}

LiveCart.AjaxRequest = Class.create();
LiveCart.AjaxRequest.prototype = {

	onComplete: false,
    
    indicatorContainerId: false,
    
	initialize: function(formOrUrl, indicatorId, onComplete)
    {
        var url = "";
        var method = "";
        var params = "";
        
        this.onComplete = onComplete;
        
        if (typeof formOrUrl == "object")
        {
            var form = formOrUrl;
            url = form.action;
            method = form.method;
            params = Form.serialize(form);
        }
        else
        {
            url = formOrUrl;
            method = "post";
        }
               
        this.indicatorContainerId = indicatorId;
        Element.show(this.indicatorContainerId);
        var updaterOptions = { method: method,
                               parameters: params,
                               onComplete: this.postProcessResponse.bind(this),
                               onFailure: this.reportError
                               };
       
		document.body.style.cursor = 'progress';
		
        new Ajax.Request(url,
                         updaterOptions);
    },

	hideIndicator: function()
	{
		Element.hide(this.indicatorContainerId);
	},

	showIndocator: function()
	{
		Element.show(this.indicatorContainerId);
	},

    postProcessResponse: function(response)
    {
		document.body.style.cursor = 'default';
        if (this.onComplete)
        {
		  	this.onComplete(response);
		}
		this.hideIndicator();        
    },

    reportError: function(response)
    {
        alert('Error!\n\n' + response.responseText);
    }
}

LiveCart.AjaxUpdater = Class.create();
LiveCart.AjaxUpdater.prototype = {

    indicatorContainerId: null,

    initialize: function(formOrUrl, container, indicator, insertionPosition)
    {
        var url = "";
        var method = "";
        var params = "";
        
        var containerId = $(container);
        var indicatorId = $(indicator);
        
        if (typeof formOrUrl == "object")
        {
            var form = formOrUrl;
            url = form.action;
            method = form.method;
            params = Form.serialize(form);
        }
        else
        {
            url = formOrUrl;
            method = "post";
        }
        LiveCart.ajaxUpdaterInstance = this;
        this.indicatorContainerId = indicatorId;
        Element.show(this.indicatorContainerId);

        var updaterOptions = { method: method,
                               parameters: params,
                               onComplete: this.postProcessResponse.bind(this),
                               onFailure: this.reportError.bind(this)
                               };

        if (insertionPosition != undefined)
        {
            switch(insertionPosition)
            {
                case 'top':
                    updaterOptions.insertion = Insertion.Top;
                break;

                case 'bottom':
                    updaterOptions.insertion = Insertion.Bottom;
                break;

                case 'before':
                    updaterOptions.insertion = Insertion.Before;
                break;

                case 'after':
                    updaterOptions.insertion = Insertion.After;
                break;

                default:
                    alert('Invalid insertion position value in AjaxUpdater');
                break;
            }
        }
        
		document.body.style.cursor = 'progress';
		
        new Ajax.Updater({success: containerId},
                         url,
                         updaterOptions); 
    },


	hideIndicator: function()
	{
		Element.hide(LiveCart.ajaxUpdaterInstance.indicatorContainerId);
	},

	showIndocator: function()
	{
		Element.show(this.indicatorContainerId);
	},

    postProcessResponse: function(response)
    {
		document.body.style.cursor = 'default';
        response.responseText.evalScripts();  
        LiveCart.ajaxUpdaterInstance.hideIndicator();

        if (this.onComplete)
        {
		  	this.onComplete(response);
		}        
    },

    reportError: function(response)
    {
        alert('Error!\n\n' + response.responseText);
    }
}

/**
 * Converts an XMLDocument into HTMLElement
 *
 * Useful when receiving partial page content as XML via AJAX, which can be transformed to
 * inserted into document as HTMLElement.
 *
 * <code>
 * 		item = xml2HtmlElement(request.responseXML.firstChild);
 *		document.getElementById('someList').appendChild(item);
 * </code>
 *
 * Don't forget to set the correct Content-type header before sending the content:
 * <code>
 *      $response->setHeader('Content-type', 'application/xml');
 * </code>
 *
 * @param xml Element
 * @return HTMLElement
 */
function xml2HtmlElement(xml)
{
	var k = 0;
	var a = 0;
	var el = 0;
	var child = 0;

	if ('#text' == xml.nodeName)
	{
		el = document.createTextNode(xml.nodeValue);
	}
	else
	{
	  	el = document.createElement(xml.nodeName);
		el.nodeValue = xml.nodeValue;
		if (xml.attributes.length > 0)
		{
		  	for (a = 0; a < xml.attributes.length; a++)
		  	{
			    att = xml.attributes[a];
				el.setAttribute(att.name, att.value);
			}
		}
		if (xml.childNodes.length > 0)
		{
			for (k = 0; k < xml.childNodes.length; k++)
			{
				child = xml2HtmlElement(xml.childNodes[k]);
				el.appendChild(child);
			}
		}
	}
	return el;
}