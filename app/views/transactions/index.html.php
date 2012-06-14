<h2>Transactions</h2>
<ol>
	<?php foreach($transactions as $_transaction): ?>
		<li>
			<a href="/transactions/view/<?=$_transaction->_id?>" class="view-transaction"><?=$_transaction->title?></a>
			<span class="transaction-value"><?=$_transaction->value?></span>
		</li>
	<?php endforeach; ?>
</ul>