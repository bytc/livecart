<p>All the transactions made in your e-store are considered as <i>orders</i> which are placed directly by customers in the storefront or by administrators (phone, email orders).</p>


<div class="tasks">
<fieldset>
<legend>Things you can do</legend>
<ul>	
	<li><a href="{self}#view">View and Sort Orders</a></li>
	<li><a href="{self}#find">Find Orders</a></li>
	<li><a href="{self}#manage">Manage Orders</a></li>
	<li><a href="{self}#create">Create a new Order</a></li>
</ul>
</fieldset>
</div>

<h3 id="view">View and Sort Orders</h3>

<p>When you access the orders section, all order are displayed. You can select one of the order groups by clicking it in the group tree:</p>

<img src="image/doc/orders/tree.png"/>

<ul>
	<li>New - the most recent orders.</li>
	<li>Backordered - orders that can't be fulfilled because of stock shortage.</li>
	<li>Awaiting Shipment - orders that have been approved.</li>
	<li>Shipped - orders that have been sent to a customer.</li>
	<li>Returned orders usually fail to reach the recipient or for some reason are returned by a customer.</li>
</ul>

<p class="top">Orders are displayed in a table similar to this:</p>
<img src="image/doc/orders/orders.png"/>

<p class="topp">By default orders are displayed from the latest to the oldest as they were placed. To <strong>sort</strong> orders, click the "arrow" icon next to appropriate attribute:</p>
<img src="image/doc/orders/sort.png"/>

<div id="attributes"></div>
<p class="topp">You can also define what attributes should be displayed in the menu:</p>
<ol>
	<li>Click the "Columns" link at the right:</li>
	<img src="image/doc/orders/columns.bmp"/>
	<li>Add or remove attributes by marking or clearing the checkboxes:</li>
	<img src="image/doc/orders/checkboxes.bmp"/>
	<li>Click the "Change columns" button.</li>
</ol>

<h3 id="find">Find Orders</h3>

<p>You can search for orders using one of the attributes on the toolbar.</p>
<ol>
	<li>Select order group in the group tree</li>
	<li>Click an attribute to activate its field:</li>
	<img src="image/doc/orders/search1.bmp"/>
	<li>Supply search criteria and press enter:</li>
	<img src="image/doc/orders/search2.bmp"/>
</ol>
<p>Results that match your criteria appear below. You can as well define attributes displayed in the toolbar. <a href="{self}#attributes"><small>(Tell me how)</small></a></p>

<h3 id="manage">Manage Orders</h3>
<p>You can quickly manage your orders by selecting multiple orders for processing:</p>
<ul>
	<li>Select orders by marking a checkbox at the left:</li>
	<img src="image/doc/orders/orders.png"/>
	<li>With selected - click a drop-down list and select an action to apply.</li>
	<li>Click the "Process" button to save changes.</li>
</ul>

<p class="topp">To edit a <strong>single order</strong>, click the "view order" link. Order info page opens. Here you can manage all the order's details:</p>

<ul>
	<li><a href="{self}#overview">Overview</a></li>
	<li><a href="{self}#products">Products</a></li>
	<li><a href="{self}#payments">Payments</a> </li>
	<li><a href="{self}#feedback">Customer feedback</a></li>
</ul>

<h4 id="details">Edit main details</h4>

<img src="image/doc/orders/overview_tab.png"/>

<p>In the order's Overview tab you can:</p>

<ul>
	<li>Set orders status. The status may set be to one of the following states:</li>
	<ul class="subList">
		<li>New - </li>
		<li>Accepted - </li>
		<li>Accepted - </li>
		<li>Accepted - </li>
	</ul>
	<p class="note"><strong>Note</strong>: once you change order's status to shiped, you can no longer change any order's details (...)</p>
	<li>Print invoice</li>
	<li>Edit shipping address</li>
	<li>Edit billing address</li>
</ul>

<h4 id="products" class="topp">Edit products</h4>

<img src="image/doc/orders/products_tab.png"/>


<p><h5 class="top">Create new shippment</h5></p>
<p>In the "Products" section you can create shipments to divide products into different packages. To create a new shippment, click the "Add new shippment" link and click "yes" to confirm. You can add products to shipments by draging and droping them between the shippments.</p>

<p class="note"><strong>Note</strong>: Shipments are different part of the same order. Sometimes if any of the items are not available at the moment, according to the customer's preference it might be chosen to split an order into separate shipments. Usually the rest of the parcel is sent when the the products become available.</p>

<p><h5 class="top">Set shippment's status</h5></p>
<p>When a new shippment is created, it's status is "xxx". You can set shipment's status by clicking the "Status" drop-down list and selecting on of the status:</p>

<ul class="subList">
	<li>New</li>
	<li>Pending</li>
	<li>Waiting</li>
</ul>	
<p class="note"><strong>Note</strong>: Once you change the status of shipment to "shipped" you can no longer (...)</p>

<p><h5 class="top">Change shipping service</h5></p>
<p>A shipping service is originally defined by users in your web store. To change the shipping service, click the "Change shipping service" link and select a service from the list. Click "Save" to update.</p>

<p><h5 class="top">Add more products</h5></p>
<p>You can also add more products to any of your shipments. To do that, click the "Add new product" link and select products to add. All products have editable quantities thus you can update the product amount if necessary.</p>

<p><h5 class="top">Report</h5></p>
<p>Report displays the order's brief summary with total price, taxes and shipping costs.</p>

<p><h5 class="top">Downloadable</h5></p>
<p>If there are any downloadable products in the order, they are displayed in the "Downloadable" section. You can update product count or add new downloadable products.</p>
	
	
<h4 id="payments" class="topp">Manage Payments</h4>

<img src="image/doc/orders/payments_tab.png"/>

<p>In the Payments section you can process order's payments. This can be done by processing already authorized payment or adding new payment.</p>


<h5 class="top">Capture or void payments authorized by users</h5>
<p>After your users complete the order and authorization carried out to validate the payment is approved, you can capture money to complete the transfer (aquire money). On the contrary, if the order is canceled you can cancel the payment making it void.</p>

<h5 class="top">Add offline payments</h5>
<p>In case you accept offline payments you can add an offline payment by clicking the "Add offline payment" link.</p>

<h5 class="top">Add credit card payments</h5>


<h4 id="feedback" class="topp">Communication</h4>

<img src="image/doc/orders/communication_tab.png"/>

<p>Communication section is intended for communication between the customer and store's representatives in regard with a specific transaction. Users can send notes while the representative is able to post responses.</p>
<p>To add a response for the customer, click the "Add responce" link and type a message. Click "Add Response" to send it.</p>

<h4 id="feedback" class="topp">Change history</h4>

<img src="image/doc/orders/change_tab.png"/>

<p>Change history serves as a log of changes since the order was created up to the recent moment.</p>

<h3 id="create" class="topp">Create new order</h3>

<p>Orders can be created (...)</p>

