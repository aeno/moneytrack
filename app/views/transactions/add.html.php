<?php

use lithium\core\Libraries;
use lithium\core\Environment;
use lithium\data\Connections;

$this->title('Add Transaction');

$self = $this;

?>

<?=$this->form->create()?>
	<?=$this->form->label('Title')?>
	<?=$this->form->text('title')?>
	
	<?=$this->form->label('Value')?>
	<?=$this->form->text('value')?>
	
	<?=$this->form->submit('Add Transaction')?>
<?=$this->form->end()?>