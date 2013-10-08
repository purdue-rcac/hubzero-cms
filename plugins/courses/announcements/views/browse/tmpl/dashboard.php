<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$rows = $this->offering->announcements(array(
	'limit'     => $this->params->get('display_limit', 1), 
	'published' => true
));
?>
	<div class="sub-section announcements">
		<div class="grid">
			<div class="col span-half">
				<h3>Last Announcement</h3>
		<?php
		if ($rows->total() > 0)
		{
			foreach ($rows as $row)
			{
				?>
				<div class="announcement<?php if ($row->get('priority')) { echo ' high'; } ?>">
					<?php echo $row->content('parsed'); ?>
					<dl class="entry-meta">
						<dt class="entry-id"><?php echo $row->get('id'); ?></dt> 
						<dd class="time">
							<time datetime="<?php echo $row->published(); ?>">
								<?php echo $row->published('time'); ?>
							</time>
						</dd>
						<dd class="date">
							<time datetime="<?php echo $row->published(); ?>">
								<?php echo $row->published('date'); ?>
							</time>
						</dd>
					</dl>
				</div>
				<?php
			}
		}
		else
		{
		?>
			<p>No announcements made.</p>
		<?php
		}
		?>
			</div>

			<div class="col span-half omega">
				<h3>New Announcement</h3>
				<form action="<?php echo JRoute::_('index.php?option=' . $this->option . '&gid=' . $this->course->get('alias') . '&offering=' . $this->offering->get('alias') . '&active=announcements'); ?>" method="post" id="announcementForm" class="full">
					<fieldset>
						<legend>
							<?php echo JText::_('PLG_COURSES_ANNOUNCEMENTS_NEW'); ?>
						</legend>

						<label for="field_content">
							<span><?php echo JText::_('Announcement'); ?></span>
							<?php
							ximport('Hubzero_Wiki_Editor');
							$editor =& Hubzero_Wiki_Editor::getInstance();
							echo $editor->display('fields[content]', 'field_content', '', 'minimal no-footer', '35', '3');
							?>
						</label>

						<label for="field-priority" id="priority-label">
							<input class="option" type="checkbox" name="fields[priority]" id="field-priority" value="1" /> 
							<?php echo JText::_('Mark as high priority'); ?>
						</label>

						<p class="submit">
							<input type="submit" value="<?php echo JText::_('PLG_COURSES_ANNOUNCEMENTS_SUBMIT'); ?>" />
						</p>
					</fieldset>
					<div class="clear"></div>

					<input type="hidden" name="fields[id]" value="" />
					<input type="hidden" name="fields[state]" value="1" />
					<input type="hidden" name="fields[offering_id]" value="<?php echo $this->offering->get('id'); ?>" />
					<input type="hidden" name="fields[section_id]" value="<?php echo $this->offering->section()->get('id'); ?>" />

					<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
					<input type="hidden" name="gid" value="<?php echo $this->course->get('alias'); ?>" />
					<input type="hidden" name="offering" value="<?php echo $this->offering->get('alias') . ($this->offering->section()->get('alias') != '__default' ? ':' . $this->offering->section()->get('alias') : ''); ?>" />
					<input type="hidden" name="active" value="announcements" />
					<input type="hidden" name="action" value="save" />
				</form>
			</div>
		</div>
	</div>