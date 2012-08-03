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

/**
 * Table class for substituting tags for another tag
 */
class TagsSubstitute extends JTable
{
	/**
	 * int(11)
	 * 
	 * @var integer
	 */
	var $id         = NULL;

	/**
	 * varchar(255)
	 * 
	 * @var string
	 */
	var $tag        = NULL;

	/**
	 * varchar(255)
	 * 
	 * @var string
	 */
	var $raw_tag    = NULL;

	/**
	 * int(11)
	 * 
	 * @var integer
	 */
	var $tag_id     = NULL;

	/**
	 * int(11)
	 * 
	 * @var integer
	 */
	var $created_by = NULL;

	/**
	 * datetime(0000-00-00 00:00:00)
	 * 
	 * @var string
	 */
	var $created    = NULL;

	/**
	 * Constructor
	 * 
	 * @param      object &$db JDatabase
	 * @return     void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__tags_substitute', 'id', $db);
	}

	/**
	 * Validate data
	 * 
	 * @return     True if data is valid
	 */
	public function check()
	{
		if ($this->tag && !$this->raw_tag)
		{
			$this->raw_tag = $this->tag;
		}
		$this->raw_tag = trim($this->raw_tag);
		if (!$this->raw_tag) 
		{
			$this->setError(JText::_('You must enter a tag.'));
			return false;
		}

		$this->tag = $this->normalize($this->raw_tag);

		if (!$this->id) 
		{
			$juser =& JFactory::getUser();
			$this->created = date('Y-m-d H:i:s', time());  // use gmdate() ?
			$this->created_by = $juser->get('id');
		}

		if (!$this->tag_id)
		{
			$this->setError(JText::_('You must enter the ID of the tag to substitute this tag for.'));
			return false;
		}

		return true;
	}

	/**
	 * Normalize a raw tag
	 * Strips all non-alphanumeric characters
	 * 
	 * @param      string $tag Raw tag
	 * @return     string
	 */
	public function normalize($tag)
	{
		return strtolower(preg_replace("/[^a-zA-Z0-9]/", '', $tag));
	}

	/**
	 * Remove all tag references for a given object
	 * 
	 * @param      integer $tag_id Tag ID
	 * @return     boolean True if records removed
	 */
	public function removeForTag($tag_id=null)
	{
		if (!$tag_id) 
		{
			$tag_id = $this->tag_id;
		}
		if (!$tag_id) 
		{
			$this->setError(JText::_('Missing argument.'));
			return false;
		}

		$sql = "DELETE FROM $this->_tbl WHERE tag_id='$tag_id'";

		$this->_db->setQuery($sql);
		if (!$this->_db->query()) 
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}

	/**
	 * Get a record count for a tag ID
	 * 
	 * @param      integer $tag_id Tag ID
	 * @return     mixed Integer if successful, false if not
	 */
	public function getCount($tag_id=null)
	{
		if (!$tag_id) 
		{
			$tag_id = $this->tag_id;
		}
		if (!$tag_id) 
		{
			$this->setError(JText::_('Missing argument.'));
			return false;
		}

		$this->_db->setQuery("SELECT COUNT(*) FROM $this->_tbl WHERE tag_id='$tag_id'");
		return $this->_db->loadResult();
	}

	/**
	 * Get all the tags on an object
	 * 
	 * @param      integer $tag_id Tag ID
	 * @param      integer $offset Record offset
	 * @param      integer $limit  Number of records to return (returns all if less than 1)
	 * @return     mixed Array if successful, false if not
	 */
	public function getRecords($tag_id=null, $offset=0, $limit=0)
	{
		if (!$tag_id) 
		{
			$tag_id = $this->tag_id;
		}
		if (!$tag_id) 
		{
			$this->setError(JText::_('Missing argument.'));
			return false;
		}

		$sql = "SELECT * FROM $this->_tbl WHERE tag_id='$tag_id' ORDER BY raw_tag ASC";
		if ($limit > 0) 
		{
			$sql .= " LIMIT $offset, $limit";
		}

		$this->_db->setQuery($sql);
		return $this->_db->loadAssocList('tag');
	}

	/**
	 * Get all the tags on an object
	 * 
	 * @param      integer $tag_id Tag ID
	 * @param      integer $offset Record offset
	 * @param      integer $limit  Number of records to return (returns all if less than 1)
	 * @return     string
	 */
	public function getRecordString($tag_id=null, $offset=0, $limit=0)
	{
		$items = $this->getRecords($tag_id, $offset, $limit);

		$subs = array();
		if ($items)
		{
			foreach ($items as $k => $item)
			{
				$subs[] = $item['raw_tag'];
			}
		}
		return implode(', ', $subs);
	}
}

