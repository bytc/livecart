{pageTitle}{t _view_order} #{$order.ID}{/pageTitle}
{loadJs form=true}
<div class="userViewOrder">

{include file="layout/frontend/header.tpl"}
{* include file="layout/frontend/leftSide.tpl" *}
{* include file="layout/frontend/rightSide.tpl" *}

<div id="content" class="left right">

    <h1>{t _view_order} #{$order.ID} ({$order.formatted_dateCompleted.date_long})</h1>
    
	{include file="user/userMenu.tpl" current="ordersMenu"}    
    
    <div id="userContent">
    
        <fieldset class="container">
    
        <label class="title">{t Order ID}:</label>
        <label class="text">{$order.ID}</label>
        <div class="clear"></div>       
    
        <label class="title">{t Order placed}:</label>
        <label class="text">{$order.formatted_dateCompleted.date_long}</label>
        <div class="clear"></div>   
    
        <label class="title">{t Order total}:</label>
        <label class="text">{$order.formattedTotal[$order.Currency.ID]}</label>
        <div class="clear"></div>   
    
        <label class="title">{t Order status}:</label>
        <label class="text">{include file="user/orderStatus.tpl" order=$order}</label>
        <div class="clear"></div>   

        {if !$order.isCancelled}
        <p>
            <a href="{link controller=user action=orderInvoice id=`$order.ID`}" target="_blank" class="invoice">{t _order_invoice}</a>
        </p>
        {/if}
    
    	{foreach from=$order.shipments item="shipment" name="shipments"}
    	   
            {if $shipment.items}
            
            {if !$shipment.isShippable}
                <h2>{t _downloads}</h2>        
            {elseif $smarty.foreach.shipments.total > 1}
                <h2>{t Shipment} #{$smarty.foreach.shipments.iteration}</h2>        
                <p>
                    {t Status}: {include file="user/shipmentStatus.tpl" shipment=$shipment}
                </p>
            {else}
                <h2>{t _ordered_products}</h2>
            {/if}
    	
            <table class="table shipment">
            
                <thead>
                    <tr>
                        <th class="productName">Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>                            
                </thead>
                
                <tbody>
                            
                    {include file="order/orderTableDetails.tpl"}
                            
                    {foreach from=$shipment.taxes item="tax"}
                        <tr>                    
                            <td colspan="3" class="tax">{$tax.TaxRate.Tax.name_lang}:</td>
                            <td>{$tax.formattedAmount[$order.Currency.ID]}</td>
                        </tr>
                    {/foreach}        
                      
                    <tr>
                        <td colspan="3" class="subTotalCaption">
                            {if $smarty.foreach.shipments.total > 1}
                                {t _shipment_total}:
                            {else}
                                {t _order_total}:                        
                            {/if}
                        </td>
                        <td class="subTotal">{$shipment.formatted_totalAmount}</td>            
                    </tr>
                                            
                </tbody>
            
            </table>
            
            {/if}
    	
    	{/foreach}
    	
        {defun name="address"}
            {if $address}
                <p>
                    {$address.fullName}                
                </p>
                <p>
                    {$address.companyName}                
                </p>
                <p>
                    {$address.address1}
                </p>
                <p>
                    {$address.address2}
                </p>
                <p>
                    {$address.city}
                </p>
                <p>
                    {if $address.stateName}{$address.stateName}, {/if}{$address.postalCode}
                </p>
                <p>
                    {$address.countryName}
                </p>
            {/if}
        {/defun}    	
    	
        <div id="overviewAddresses">
        
            {if $order.ShippingAddress}
            <div style="width: 50%; float: left;">
                <h3>{t Order is shipped to}:</h3>
                {fun name="address" address=$order.ShippingAddress}
            </div>    
            {/if}
            
            <div style="width: 50%; float: left;">
                <h3>{t Order is billed to}:</h3>
                {fun name="address" address=$order.BillingAddress}
            </div>    
        
        </div>    	
    	
    	</fieldset>
    	
    	<h2 id="msg">Support</h2>
    	
    	<p class="noteAbout">Have questions regarding your order? Here is the place to ask them and get answers.</p>
        
        {if $notes}    	
           <ul class="notes">
        	   {foreach from=$notes item=note}
        	       {include file="user/orderNote.tpl" note=$note}
        	   {/foreach}
    	   </ul>
    	{/if}
    	
    	{form action="controller=user action=addNote id=`$order.ID`" method=POST id="noteForm" handle=$noteForm}
    	   {err for="text"}
    	       {{label {t Enter your question or response}:}}
    	       {textarea}
    	   {/err}    	
           <input type="submit" class="submit" value="{tn _submit_response}" />
    	{/form}
	
	</div>

</div>

{include file="layout/frontend/footer.tpl"}    

</div>