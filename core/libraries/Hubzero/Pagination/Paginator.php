<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Pagination;

use Hubzero\Base\Object;
use Hubzero\Html\Builder\Grid;

/**
 * Pagination Class. Provides a common interface for content pagination for the platform.
 *
 * Inspired by Joomla's JPagination class
 */
class Paginator extends Object
{
	/**
	 * The record number to start displaying from.
	 *
	 * @var  integer
	 */
	public $limitstart = null;

	/**
	 * Number of rows to display per page.
	 *
	 * @var  integer
	 */
	public $limit = null;

	/**
	 * Total number of rows.
	 *
	 * @var  integer
	 */
	public $total = null;

	/**
	 * Prefix used for request variables.
	 *
	 * @var  integer
	 */
	public $prefix = null;

	/**
	 * View all flag
	 *
	 * @var  boolean
	 */
	protected $_viewall = false;

	/**
	 * A list of pagination limits the user can select from
	 *
	 * @var  array
	 */
	protected $_limits = array();

	/**
	 * Additional URL parameters to be added to the pagination URLs generated by the class.  These
	 * may be useful for filters and extra values when dealing with lists and GET requests.
	 *
	 * @var  array
	 */
	protected $_additionalUrlParams = array();

	/**
	 * Constructor.
	 *
	 * @param   integer  $total       The total number of items.
	 * @param   integer  $limitstart  The offset of the item to start at.
	 * @param   integer  $limit       The number of items to display per page.
	 * @param   string   $prefix      The prefix used for request variables.
	 * @return  void
	 */
	public function __construct($total, $limitstart, $limit, $prefix = '')
	{
		// Value/type checking.
		$this->total      = (int) $total;
		$this->limitstart = (int) max($limitstart, 0);
		$this->limit      = (int) max($limit, 0);
		$this->prefix     = $prefix;

		if ($this->limit > $this->total)
		{
			$this->limitstart = 0;
		}

		// Set the pagination iteration loop values.
		$displayedPages = 10;

		if (!$this->limit)
		{
			$this->limit = $total;
			$this->limitstart = 0;

			// If we are viewing all records set the view all flag to true.
			$this->_viewall = true;
		}

		// If limitstart is greater than total (i.e. we are asked to display records that don't exist)
		// then set limitstart to display the last natural page of results
		if ($this->limitstart > $this->total - $this->limit)
		{
			$this->limitstart = max(0, (int) (ceil($this->total / $this->limit) - 1) * $this->limit);
		}

		// Set the total pages and current page values.
		if ($this->limit > 0)
		{
			$this->set('pages.total', ceil($this->total / $this->limit));
			$this->set('pages.current', ceil(($this->limitstart + 1) / $this->limit));
		}
		else
		{
			$this->set('pages.total', 1);
			$this->set('pages.current', $this->limitstart + 1);
		}

		// Completely rewritten to center active page - zooley (2012-08-10)
		$this->set('pages.middle', ceil($displayedPages / 2));

		$start_loop = $this->get('pages.current') - $this->get('pages.middle') + 1;
		$start_loop = ($start_loop < 1 ? 1 : $start_loop);
		$stop_loop  = $this->get('pages.current') + $displayedPages - $this->get('pages.middle');

		$i = $start_loop;
		if ($stop_loop > $this->get('pages.total'))
		{
			$i = $i + ($this->get('pages.total') - $stop_loop);
			$stop_loop = $this->get('pages.total');
		}
		if ($i <= 0)
		{
			$stop_loop = $stop_loop + (1 - $i);
			$i = 1;
		}

		$this->set('pages.i', $i);
		$this->set('pages.start', $start_loop);
		$this->set('pages.stop', $stop_loop);

		$this->_limits = array();
		for ($i = 5; $i <= 30; $i += 5)
		{
			$this->_limits[] = $i;
		}
		$this->_limits[] = 50;
		$this->_limits[] = 100;
		$this->_limits[] = 500;
		$this->_limits[] = 1000;
	}

	/**
	 * Method to set an additional URL parameter to be added to all pagination class generated
	 * links.
	 *
	 * @param   string  $key    The name of the URL parameter for which to set a value.
	 * @param   mixed   $value  The value to set for the URL parameter.
	 * @return  object  Paginator
	 */
	public function setAdditionalUrlParam($key, $value)
	{
		// Get the old value to return and set the new one for the URL parameter.
		$result = isset($this->_additionalUrlParams[$key]) ? $this->_additionalUrlParams[$key] : null;

		// If the passed parameter value is null unset the parameter, otherwise set it to the given value.
		if ($value === null)
		{
			unset($this->_additionalUrlParams[$key]);
		}
		else
		{
			$this->_additionalUrlParams[$key] = $value;
		}

		return $this;
	}

	/**
	 * Method to get an additional URL parameter (if it exists) to be added to
	 * all pagination class generated links.
	 *
	 * @param   string  $key  The name of the URL parameter for which to get the value.
	 * @return  mixed   The value if it exists or null if it does not.
	 */
	public function getAdditionalUrlParam($key)
	{
		$result = isset($this->_additionalUrlParams[$key]) ? $this->_additionalUrlParams[$key] : null;

		return $result;
	}

	/**
	 * Return the rationalised offset for a row with a given index.
	 *
	 * @param   integer  $index  The row index
	 * @return  integer  Rationalised offset for a row with a given index.
	 */
	public function getRowOffset($index)
	{
		return $index + 1 + $this->limitstart;
	}

	/**
	 * Return the pagination data object, only creating it if it doesn't already exist.
	 *
	 * @return  object  Pagination data object.
	 */
	public function getData()
	{
		static $data;

		if (!is_object($data))
		{
			$data = $this->_buildDataObject();
		}

		return $data;
	}

	/**
	 * Set the list of limit options.
	 *
	 * @param   array   $limits  A list of limit options
	 * @return  object  Paginator
	 */
	public function setLimits($limits)
	{
		if (is_array($limits))
		{
			$this->_limits = $limits;
		}

		return $this;
	}

	/**
	 * Get the list of limit options.
	 *
	 * @return  array
	 */
	public function getLimits()
	{
		return $this->_limits;
	}

	/**
	 * Return the icon to move an item UP.
	 *
	 * @param   integer  $i          The row index.
	 * @param   boolean  $condition  True to show the icon.
	 * @param   string   $task       The task to fire.
	 * @param   string   $alt        The image alternative text string.
	 * @param   boolean  $enabled    An optional setting for access control on the action.
	 * @param   string   $checkbox   An optional prefix for checkboxes.
	 * @return  string   Either the icon to move an item up or a space.
	 */
	public function orderUpIcon($i, $condition = true, $task = 'orderup', $alt = 'JLIB_HTML_MOVE_UP', $enabled = true, $checkbox = 'cb')
	{
		if (($i > 0 || ($i + $this->limitstart > 0)) && $condition)
		{
			return Grid::orderUp($i, $task, '', $alt, $enabled, $checkbox);
		}

		return '&#160;';
	}

	/**
	 * Return the icon to move an item DOWN.
	 *
	 * @param   integer  $i          The row index.
	 * @param   integer  $n          The number of items in the list.
	 * @param   boolean  $condition  True to show the icon.
	 * @param   string   $task       The task to fire.
	 * @param   string   $alt        The image alternative text string.
	 * @param   boolean  $enabled    An optional setting for access control on the action.
	 * @param   string   $checkbox   An optional prefix for checkboxes.
	 * @return  string   Either the icon to move an item down or a space.
	 */
	public function orderDownIcon($i, $n, $condition = true, $task = 'orderdown', $alt = 'JLIB_HTML_MOVE_DOWN', $enabled = true, $checkbox = 'cb')
	{
		if (($i < $n - 1 || $i + $this->limitstart < $this->total - 1) && $condition)
		{
			return Grid::orderDown($i, $task, '', $alt, $enabled, $checkbox);
		}

		return '&#160;';
	}

	/**
	 * Create and return the pagination data object.
	 *
	 * @return  object  Pagination data object.
	 */
	protected function _buildDataObject()
	{
		$this->setAdditionalUrlParam('limit', $this->limit);

		// Initialise variables.
		$data = new \stdClass;

		// Build the additional URL parameters string.
		$params = '';
		if (!empty($this->_additionalUrlParams))
		{
			foreach ($this->_additionalUrlParams as $key => $value)
			{
				$params .= '&' . $key . '=' . $value;
			}
		}

		$data->all = new Item(\Lang::txt('JLIB_HTML_VIEW_ALL'), $this->prefix);
		if (!$this->_viewall)
		{
			$data->all->base = '0';
			$data->all->link = \Route::url($params . '&' . $this->prefix . 'limitstart=');
		}

		// Set the start and previous data objects.
		$data->start    = new Item(\Lang::txt('JLIB_HTML_START'), $this->prefix);
		$data->previous = new Item(\Lang::txt('JPREV'), $this->prefix);

		if ($this->get('pages.current') > 1)
		{
			$page = ($this->get('pages.current') - 2) * $this->limit;

			// Set the empty for removal from route
			//$page = $page == 0 ? '' : $page;

			$data->start->base = '0';
			$data->start->link = \Route::url($params . '&' . $this->prefix . 'limitstart=0');

			$data->previous->base = $page;
			$data->previous->link = \Route::url($params . '&' . $this->prefix . 'limitstart=' . $page);
		}

		// Set the next and end data objects.
		$data->next = new Item(\Lang::txt('JNEXT'), $this->prefix);
		$data->end  = new Item(\Lang::txt('JLIB_HTML_END'), $this->prefix);

		if ($this->get('pages.current') < $this->get('pages.total'))
		{
			$next = $this->get('pages.current') * $this->limit;
			$data->next->base = $next;
			$data->next->link = \Route::url($params . '&' . $this->prefix . 'limitstart=' . $next);

			$end  = ($this->get('pages.total') - 1) * $this->limit;
			$data->end->base  = $end;
			$data->end->link  = \Route::url($params . '&' . $this->prefix . 'limitstart=' . $end);
		}

		// Set the pages.
		$data->pages = array();

		for ($i = $this->get('pages.start'); $i <= $this->get('pages.stop'); $i++)
		{
			$offset = ($i - 1) * $this->limit;

			// Set the empty for removal from route
			//$offset = $offset == 0 ? '' : $offset;

			$data->pages[$i] = new Item($i, $this->prefix);
			if ($i != $this->get('pages.current')) // || $this->_viewall)
			{
				$data->pages[$i]->rel  = (($i + 1) == $this->get('pages.current')) ? 'prev' : '';
				$data->pages[$i]->rel  = (($i - 1) == $this->get('pages.current')) ? 'next' : $data->pages[$i]->rel;
				$data->pages[$i]->base = $offset;
				$data->pages[$i]->link = \Route::url($params . '&' . $this->prefix . 'limitstart=' . $offset);
			}
		}

		return $data;
	}

	/**
	 * Return the pagination footer.
	 *
	 * @param   object  $view  Optional View object to use
	 * @return  string  Pagination footer.
	 */
	public function render($view = null)
	{
		$this->set('pages.ellipsis', false);

		// Build the page navigation list.
		$data = $this->getData();

		$data->prefix    = $this->prefix;
		$data->i         = $this->get('pages.i');
		$data->ellipsis  = $this->get('pages.ellipsis');
		$data->total     = $this->get('pages.total');
		$data->startloop = $this->get('pages.start');
		$data->stoploop  = $this->get('pages.stop');
		$data->current   = $this->get('pages.current');

		if (is_array($view))
		{
			$view = new View($view);
		}

		if (!$view instanceof View)
		{
			$view = new View();
		}
		$view->set('limit', $this->limit)
		     ->set('start', $this->limitstart)
		     ->set('total', $this->total)
		     ->set('pages', $data)
		     ->set('viewall', $this->_viewall)
		     ->set('limits', $this->_limits)
		     ->set('prefix', $this->prefix);

		return $view->loadTemplate();
	}

	/**
	 * Magic method to convert the object to a string gracefully.
	 *
	 * @return  string  The entire pagination footer
	 */
	public function __toString()
	{
		return $this->render();
	}
}
