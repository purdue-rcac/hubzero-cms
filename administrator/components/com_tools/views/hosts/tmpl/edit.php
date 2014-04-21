<?php
// No direct access
defined('_JEXEC') or die( 'Restricted access' );

$text = ( $this->task == 'edit' ? JText::_( 'Edit Host' ) : JText::_( 'New Host' ) );

JToolBarHelper::title( JText::_( 'Tools' ).': <small><small>[ '. $text.' ]</small></small>', 'tools.png' );
JToolBarHelper::save();
JToolBarHelper::cancel();

jimport('joomla.html.editor');
$editor = JEditor::getInstance();

?>
<script type="text/javascript">
function submitbutton(pressbutton) 
{
	var form = document.adminForm;

	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}

	submitform( pressbutton );
}
</script>

<form action="index.php" method="post" name="adminForm" id="item-form">
	<div class="col width-50 fltlft">
		<fieldset class="adminform">
			<legend><span><?php echo JText::_('Details'); ?></span></legend>

			<div class="input-wrap">
				<label for="field-hostname"><?php echo JText::_('Hostname'); ?>:</label><br />
				<input type="text" name="fields[hostname]" id="field-hostname" size="30" maxlength="255" value="<?php echo $this->escape(stripslashes($this->row->hostname)); ?>" />
			</div>

			<div class="input-wrap">
				<label for="field-hosttype"><?php echo JText::_('Host types'); ?>:</label><br />
				<select multiple="multiple" size="10" name="hosttype[]" id="field-hosttype">
				<?php
					for ($i=0; $i<count($this->hosttypes); $i++)
					{
						$r = $this->hosttypes[$i];
						if ((int)$r->value & (int)$this->row->provisions) { ?>
						<option selected="selected" value="<?php echo $r->name; ?>"><?php echo $r->name; ?></option>
						<?php } else { ?>
						<option value="<?php echo $r->name; ?>"><?php echo $r->name; ?></option>
						<?php }
					}
				?>
				</select>
			</div>

			<div class="input-wrap">
				<label for="field-venue_id"><?php echo JText::_('Venue'); ?>:</label><br />
				<select name="fields[venue_id]" id="field-venue_id">
					<option value="0"><?php echo JText::_('Select...'); ?></option>
					<?php
						if ($this->zones)
						{
							foreach ($this->zones as $zone)
							{
								?>
								<option<?php if ($zone->id == $this->row->zone_id) { echo ' selected="selected"'; } ?> value="<?php echo $zone->id; ?>"><?php echo $this->escape(stripslashes($zone->zone)); ?></option>
								<?php 
							}
						}
					?>
				</select>
			</div>
		</fieldset>
	</div>
	<div class="col width-50 fltrt">
		<table class="meta">
			<tbody>
				<tr>
					<th scope="row"><?php echo JText::_('Status'); ?></th>
					<td><?php echo $this->escape($this->row->status); ?></td>
				</tr>
			</tbody>
		</table>

		<?php if (isset($this->toolCounts) && count($this->toolCounts) > 0) : ?>
			<fieldset class="adminform">
				<legend>
					<span>Tool Sessions</span>
				</legend>
				<table class="admintable">
					<tbody>
						<?php foreach($this->toolCounts as $c) : ?>
							<tr>
								<td><?php echo $c->appname; ?></td>
								<td><?php echo $c->count; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</fieldset>
		<?php endif; ?>
		
		<?php if (isset($this->statusCounts) && count($this->statusCounts) > 0) : ?>
			<fieldset class="adminform">
				<legend>
					<span>Container Statuses</span>
				</legend>
				<table class="admintable">
					<tbody>
						<?php foreach($this->statusCounts as $c) : ?>
							<tr>
								<td><?php echo $c->status; ?></td>
								<td><?php echo $c->count; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</fieldset>
		<?php endif; ?>
	</div>
	<div class="clr"></div>

	<input type="hidden" name="fields[status]" value="<?php echo ($this->row->status) ? $this->row->status : 'check'; ?>" />
	<input type="hidden" name="fields[id]" value="<?php echo $this->row->hostname; ?>" />
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="save" />

	<?php echo JHTML::_( 'form.token' ); ?>
</form>
