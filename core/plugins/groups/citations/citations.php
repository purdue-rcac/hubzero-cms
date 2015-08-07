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
	* @package		hubzero-cms
	* @author		Alissa Nedossekina <alisa@purdue.edu>, Kevin Wojkovich <kevinw@purdue.edu>
	* @copyright Copyright 2005-2015 Purdue University. All rights reserved.
	* @license		http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
	*/

// No direct access
defined('_HZEXEC_') or die();
// include needed libs
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'helpers' . DS . 'format.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'citation.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'tables' . DS . 'association.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'author.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'tables' . DS . 'secondary.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'tables' . DS . 'sponsor.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'format.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'type.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'tag.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'tagobject.php');
require_once(PATH_CORE . DS . 'components' . DS . 'com_citations' . DS . 'models' . DS . 'importer.php');

use Hubzero\Config\Registry;
use Components\Tags\Models\Tag;
use Components\Tags\Models\Cloud;
use Components\Citations\Models\Citation;
use Components\Citations\Models\Author;
use Components\Citations\Models\Type;
use Components\Citations\Models\Format;
use Components\Citations\Models\Importer;
/**
	* Groups plugin class for citations
	*/
class plgGroupsCitations extends \Hubzero\Plugin\Plugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var		 boolean
	 */
	protected $_autoloadLanguage = true;

	/**
	 * Get Tab
	 *
	 * @return array plugin tab details
	 */
	public function &onGroupAreas()
	{
		$area = array(
			'name'				 => $this->_name,
			'title'				 => Lang::txt('PLG_GROUPS_CITATIONS'),
			'default_access'	 => $this->params->get('plugin_access', 'members'),
			'display_menu_tab' => $this->params->get('display_tab', 1),
			'icon'				 => '275D'
		);
		return $area;
	}

	/**
	 * Return data on a group view (this will be some form of HTML)
	 *
	 * @param		 object  $group		 Current group
	 * @param		 string  $option		 Name of the component
	 * @param		 string  $authorized User's authorization level
	 * @param		 integer $limit		 Number of records to pull
	 * @param		 integer $limitstart Start of records to pull
	 * @param		 string  $action		 Action to perform
	 * @param		 array	 $access		 What can be accessed
	 * @param		 array	 $areas		 Active area(s)
	 * @return		 array
	 */
	public function onGroup($group, $option, $authorized, $limit=0, $limitstart=0, $action='', $access, $areas=null)
	{
		$returnhtml = true;
		$active		= 'citations';

		// The output array we're returning
		$arr = array(
			'html'		 => '',
			'metadata' => ''
		);

		// get this area details
		$this_area = $this->onGroupAreas();

		// Check if our area is in the array of areas we want to return results for
		if (is_array($areas) && $limit)
		{
			if (!in_array($this_area['name'], $areas))
			{
				$returnhtml = false;
			}
		}

		//creat database object
		$this->database = App::get('db');

		//get the group members
		$members = $group->get('members');

		// Set some variables so other functions have access
		$this->authorized = $authorized;
		$this->members		= $members;
		$this->group		= $group;
		$this->option		= $option;
		$this->action		= $action;
		$this->access		= $access;

		$this->importer = new Importer(
			App::get('db'),
			App::get('filesystem'),
			App::get('config')->get('tmp_path') . DS . 'citations',
			App::get('session')->getId()
		);


		//if we want to return content
		if ($returnhtml)
		{
			//set group members plugin access level
			$group_plugin_acl = $access[$active];

			//if were not trying to subscribe
			if ($this->action != 'subscribe')
			{
				//if set to nobody make sure cant access
				if ($group_plugin_acl == 'nobody')
				{
					$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_OFF', ucfirst($active)) . '</p>';
					return $arr;
				}

				//check if guest and force login if plugin access is registered or members
				if (User::isGuest()
				 && ($group_plugin_acl == 'registered' || $group_plugin_acl == 'members'))
					{
					$url = Route::url('index.php?option=com_groups&cn=' . $group->get('cn') . '&active=' . $active);

					App::redirect(
						Route::url('index.php?option=com_users&view=login?return=' . base64_encode($url)),
						Lang::txt('GROUPS_PLUGIN_REGISTERED', ucfirst($active)),
						'warning'
					);
					return;
				}

				//check to see if user is member and plugin access requires members
				if (!in_array(User::get('id'), $members) && $group_plugin_acl == 'members')
				{
					$arr['html'] = '<p class="info">' . Lang::txt('GROUPS_PLUGIN_REQUIRES_MEMBER', ucfirst($active)) . '</p>';
					return $arr;
				}
			}

			//run task based on action
			switch ($this->action)
			{
				case 'save':	 $arr['html'] .= $this->_save();		break;
				case 'add':		 $arr['html'] .= $this->_edit();		break;
				case 'edit':	 $arr['html'] .= $this->_edit();		break;
				case 'delete':	 $arr['html'] .= $this->_delete();		break;
				case 'publish': $arr['html'] .= $this->_publish(); 	break;
				case 'browse':	 $arr['html'] .= $this->_browse();		break;
				case 'import':	 $arr['html'] .= $this->_import();		break;
				case 'upload': 	$arr['html'] .= $this->_upload();			break;
				case 'settings': $arr['html'] .= $this->_settings();	break;
				case 'process': $arr['html']	.= $this->_process(); 	break;
				default:		 $arr['html'] .= $this->_browse();
			}
		}

		//set metadata for menu
		$arr['metadata']['count'] = \Components\Citations\Models\Citation::all()
			->where('scope', '=', 'group')
			->where('scope_id', '=', $this->group->get('gidNumber'))
			->where('published', '=', \Components\Citations\Models\Citation::STATE_PUBLISHED)
			->count();
		$arr['metadata']['alert'] = '';

		// Return the output
		return $arr;
	}

	/**
	 * Display a list of all citations, with filtering&search options.
	 *
	 * @return		 string HTML
	 */
	private function _browse()
	{
		 // Instantiate a new citations object
		$obj = $this->_filterHandler(Request::getVar('filters', array()), $this->group->get('gidNumber'));

		$count = clone $obj['citations'];
		$count = $count->count();
		$isManager		 = ($this->authorized == 'manager') ? true : false;
		$config =  new \Hubzero\Config\Registry($this->group->get('params'));
		$display = $config->get('display');

		// for first-time use
		if ($count == 0 && $isManager && !isset($display))
		{
			// have a group manager set the settings
			App::redirect(
			 Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations&action=settings'),
			 Lang::txt('Please elect your settings for this group.'),
			 'warning'
			 );
		}
		elseif ((int) $count == 0 && $isManager && isset($display))
		{
			$view = $this->view('intro', 'browse');
			$view->group = $this->group;
			$view->isManager = ($this->authorized == 'manager') ? true : false;
		}
		else
		{
			//initialize the view
			$view = $this->view('default', 'browse');

			// push objects to the view
			$view->group			 = $this->group;
			$view->option			 = $this->option;
			$view->task				 = $this->_name;
			$view->database			 = $this->database;
			$view->title			 = Lang::txt(strtoupper($this->_name));
			$view->isManager		 = ($this->authorized == 'manager') ? true : false;
			$view->config			 = $config;

		}

		//get applied filters
		$view->filters = $obj['filters'];

		// only display published citations to non-managers.	
		if ($view->isManager)
		{
			//get filtered citations
			$view->citations = $obj['citations']->paginated()->rows();
		}
		else
		{
			$view->citations = $obj['citations']
				->where('published', '=', \Components\Citations\Models\Citation::STATE_PUBLISHED)
				->paginated()
				->rows();
		}

		//get the earliest year we have citations for
		$view->earliest_year = 2001;

		// Affiliation filter
		$view->filterlist = array(
			'all'	 => Lang::txt('PLG_GROUPS_CITATIONS_ALL'),
			'aff'	 => Lang::txt('PLG_GROUPS_CITATIONS_AFFILIATED'),
			'nonaff' => Lang::txt('PLG_GROUPS_CITATIONS_NONAFFILIATED'),
			'member' => Lang::txt('PLG_GROUPS_CITATIONS_MEMBERCONTRIB')
		);

		// set default values for required filters for this view.
		$view->filters['search'] = isset($view->filters['search']) ? $view->filters['search'] : "";
		$view->filters['type'] = isset($view->filters['type']) ? $view->filters['type'] : "";
		$view->filters['tag'] = isset($view->filters['tag']) ? $view->filters['tag'] : "";
		$view->filters['author'] = isset($view->filters['author']) ? $view->filters['author'] : "";
		$view->filters['publishedin'] = isset($view->filters['publishedin']) ? $view->filters['publishedin'] : "";
		$view->filters['year_start'] = isset($view->filters['year_start']) ? $view->filters['year_start'] : "";
		$view->filters['year_end'] = isset($view->filters['year_end']) ? $view->filters['year_end'] : "";
		$view->filters['startuploaddate'] = isset($view->filters['startuploaddate']) ? $view->filters['startuploaddate'] : "";
		$view->filters['enduploaddate'] = isset($view->filters['enduploaddate']) ? $view->filters['enduploaddate'] : "";
		$view->filters['sort'] = isset($view->filters['sort']) ? $view->filters['sort'] : "";
		$view->filters['filter'] = isset($view->filters['filter']) ? $view->filters['filter'] : "";

		// Sort Filter
		$view->sorts = array(
			//'sec_cnt DESC' => Lang::txt('PLG_GROUPS_CITATIONS_CITEDBY'),
			'year DESC'		 => Lang::txt('PLG_GROUPS_CITATIONS_YEAR'),
			'created DESC' => Lang::txt('PLG_GROUPS_CITATIONS_NEWEST'),
			'title ASC'		 => Lang::txt('PLG_GROUPS_CITATIONS_TITLE'),
			'author ASC'	 => Lang::txt('PLG_GROUPS_CITATIONS_AUTHOR'),
			'journal ASC'  => Lang::txt('PLG_GROUPS_CITATIONS_JOURNAL')
		);

		// Handling ids of the the boxes checked for download
		$referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
		$session = App::get('session');

		// If it's new search remove all user citation checkmarks
		if (isset($_POST['filter']))
		{
			$view->filters['idlist'] = "";
			$session->set('idlist', $view->filters['idlist']);
		}
		else
		{
			$view->filters['idlist'] = Request::getVar('idlist', $session->get('idlist'));
			$session->set('idlist', $view->filters['idlist']);
		}

		// Reset the filter if the user came from a different section
		if (strpos($referer, "/citations/browse") == false)
		{
			$view->filters['idlist'] = "";
			$session->set('idlist', $view->filters['idlist']);
		}

		// get the preferred labeling scheme
		$view->label = "both";

		if ($view->label == "none")
		{
			$view->citations_label_class = "no-label";
		}
		elseif ($view->label == "number")
		{
			$view->citations_label_class = "number-label";
		}
		elseif ($view->label == "type")
		{
			$view->citations_label_class = "type-label";
		}
		elseif ($view->label == "both")
		{
			$view->citations_label_class = "both-label";
		}
		else
		{
			$view->citations_label_class = "both-label";
		}

		// enable coins support
		$view->coins = 1;

		// types
		$ct = \Components\Citations\Models\Type::all();
		$view->types = $ct;

		// OpenURL
		$openURL = $this->_handleOpenURL();
		$view->openurl['link'] = $openURL['link'];
		$view->openurl['text'] = $openURL['text'];
		$view->openurl['icon'] = $openURL['icon'];

		// Output HTML
		foreach ($this->getErrors() as $error)
		{
			$view->setError($error);
		}

		return $view->loadTemplate();
	}

	/**
	 * Display the form allowing to edit a citation
	 *
	 * @return		 string HTML
	 */
	private function _edit()
	{
		//create view object
		$view = $this->view('default', 'edit');

		//appends view override if this is a supergroup
		if ($this->group->isSuperGroup())
		{
			$view->addTemplatePath($this->_superGroupViewOverride('edit'));
		}

		// Check if they're logged in
		if (User::isGuest())
		{
			$this->_loginTask();
		}

		// push objects to view
		$view->group	 = $this->group;
		$view->isManager = ($this->authorized == 'manager') ? true : false;
		$view->config  = new \Hubzero\Config\Registry($this->group->get('params'));


		if ($view->isManager == false)
		{
			App::redirect(
			 Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
			 Lang::txt('PLG_GROUPS_CITATIONS_GROUP_MANAGER_ONLY'),
			 'warning'
			 );
		}

		//get the citation types
		$citationsType = \Components\Citations\Models\Type::all();
		$view->types = $citationsType->rows()->toObject();

		$fields = array();
		foreach ($view->types as $type)
		{
			if (isset($type->fields))
			{
				$f = $type->fields;
				if (strpos($f, ',') !== false)
				{
					$f = str_replace(',', "\n", $f);
				}

				$f = array_map('trim', explode("\n", $f));
				$f = array_values(array_filter($f));

				$fields[strtolower(str_replace(' ', '', $type->type_title))] = $f;
			}
		}

		// Incoming - expecting an array id[]=4232
		$id = Request::getInt('id', 0);

		// Pub author
		$pubAuthor = false;

		// Load the associations object
		$assoc = new \Components\Citations\Tables\Association($this->database);

		// Get associations
		if ($id)
		{
			$view->assocs = $assoc->getRecords(array('cid' => $id), $view->isManager);
		}

		// Is user authorized to edit citations?
		if (!$view->isManager && !$pubAuthor)
		{
			$id = 0;
		}

		// Load the object
		$view->row = \Components\Citations\Models\Citation::oneorNew($id);

		//make sure title isnt too long
		$maxTitleLength = 30;
		$shortenedTitle = (strlen($view->row->title) > $maxTitleLength)
			? substr($view->row->title, 0, $maxTitleLength) . '&hellip;'
			: $view->row->title;

		// Set the pathway
		if ($id && $id != 0)
		{
			Pathway::append($shortenedTitle, 'index.php?option=com_citations&task=view&id=' . $view->row->id);
			Pathway::append(Lang::txt('PLG_GROUPS_CITATIONS_EDIT'));
		}
		else
		{
			Pathway::append(Lang::txt('PLG_GROUPS_CITATIONS_ADD'));
		}

		// Set the page title
		Document::setTitle( Lang::txt('PLG_GROUPS_CITATIONS_CITATION') . $shortenedTitle );

		//push jquery to doc
		Document::addScriptDeclaration('var fields = ' . json_encode($fields) . ';');

		// Instantiate a new view
		$view->title	= Lang::txt(strtoupper($this->_name)) . ': ' . Lang::txt(strtoupper($this->_name) . '_' . strtoupper($this->action));

		// No ID, so we're creating a new entry
		// Set the ID of the creator
		if (!$id)
		{
			$view->row->uid = User::get('id');

			// It's new - no associations to get
			$view->assocs = array();

			//tags & badges
			$view->tags		= array();
			$view->badges = array();
		}
		else
		{
			//tags & badges
			$view->tags		= \Components\Citations\Helpers\Format::citationTags($view->row, $this->database, false);
			$view->badges = \Components\Citations\Helpers\Format::citationBadges($view->row, $this->database, false);
		}

		// Output HTML
		foreach ($this->getErrors() as $error)
		{
			$view->setError($error);
		}

		return $view->loadTemplate();
	}

	/**
	 * Save an entry
	 *
	 * @return		 void
	 */
	private function _save()
	{
		// Check if they're logged in
		if (User::isGuest())
		{
			$this->_loginTask();
		}

		// set scope & scope id in save so no one can mess with hidden form inputs
		$scope = 'group';
		$scope_id = $this->group->get('gidNumber');

		//get tags
		$tags = trim(Request::getVar('tags', ''));

		//get badges
		$badges = trim(Request::getVar('badges', ''));

		// get the citation (single) or create a new one
		$citation = \Components\Citations\Models\Citation::oneOrNew(Request::getInt('id'))
			->set(array(
				'type' => Request::getInt('type'),
				'cite' => Request::getVar('cite'),
				'ref_type' => Request::getVar('ref_type'),
				'date_submit' => Request::getVar('date_submit'),
				'date_accept' => Request::getVar('date_accept'),
				'date_publish' => Request::getVar('date_publish'),
				'year' => Request::getVar('year'),
				'month' => Request::getVar('month'),
				'author' => Request::getVar('author'),
				'author_address' => Request::getVar('author_address'),
				'editor' => Request::getVar('editor'),
				'title' => Request::getVar('title'),
				'booktitle' => Request::getVar('booktitle'),
				'short_title' => Request::getVar('short_title'),
				'journal' => Request::getVar('journal'),
				'volume' => Request::getVar('volume'),
				'number' => Request::getVar('number'),
				'pages' => Request::getVar('pages'),
				'isbn' => Request::getVar('isbn'),
				'doi' => Request::getVar('doi'),
				'call_number' => Request::getVar('call_number'),
				'accession_number' => Request::getVar('accession_number'),
				'series' => Request::getVar('series'),
				'edition' => Request::getVar('edition'),
				'school' => Request::getVar('school'),
				'publisher' => Request::getVar('publisher'),
				'institution' => Request::getVar('institution'),
				'address' => Request::getVar('address'),
				'location' => Request::getVar('location'),
				'howpublished' => Request::getVar('howpublished'),
				'url' => Request::getVar('uri'),
				'eprint' => Request::getVar('eprint'),
				'abstract' => Request::getVar('abstract'),
				'keywords' => Request::getVar('keywords'),
				'research_notes' => Request::getVar('research_notes'),
				'language' => Request::getVar('language'),
				'label' => Request::getVar('label'),
				'uid' => User::get('id'),
				'created' => Date::toSql(),
				'scope' => $scope,
				'scope_id' => $scope_id
			));

		// Store new content
		if (!$citation->save())
		{
			$this->setError($citation->getError());
			$this->_edit();
			return;
		}

		//check if we are allowing tags
		$ct1 = new \Components\Tags\Models\Cloud($citation->id, 'citations');
		$ct1->setTags($tags, User::get('id'), 0, 1, '');

		//check if we are allowing badges
		$ct2 = new \Components\Tags\Models\Cloud($citation->id, 'citations');
		$ct2->setTags($badges, User::get('id'), 0, 1, 'badge');

		// redirect after save
		App::redirect(
			Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
			Lang::txt('PLG_GROUPS_CITATIONS_CITATION_SAVED'),
			'success'
		);
		return;
	}

	/**
	 * Publish method for group citations
	 *
	 * @param null
	 * @return void
	 *
	 **/
	 private function _publish()
	 {
			//verify that the user is a manager.
			$isManager = ($this->authorized == 'manager') ? true : false;
			if (!$isManager)
			{
				//redirect to browse with admonishment
				App::redirect(
					Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
					Lang::txt('PLG_GROUPS_CITATIONS_GROUP_MANAGER_ONLY'),
					'warning'
				);
				return;
			}

			$id = Request::getVar('id', 0);

			if ($id != 0)
			{
				$citation = \Components\Citations\Models\Citation::oneOrFail($id);

				// toggle the state
				if ($citation->published != $citation::STATE_PUBLISHED)
				{
					$citation->set('published',  $citation::STATE_PUBLISHED);
					$string = 'PLG_GROUPS_CITATIONS_CITATION_PUBLISHED';
				}
				else
				{
					$citation->set('published', $citation::STATE_UNPUBLISHED);
					$string = 'PLG_GROUPS_CITATIONS_CITATION_UNPUBLISHED';
				}

				//save the state
				if ($citation->save())
				{
					App::redirect(
						Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
						Lang::txt($string),
						'success'
					);
					return;
				}
			}
			else
			{
				//error, no such citation
			}
		} //end _publish()


	/**
	 * Delete method for group citations
	 *
	 * @param null
	 * @return void
	 *
	 **/
	 private function _delete()
	 {
			//verify that the user is a manager.
			$isManager = ($this->authorized == 'manager') ? true : false;
			if (!$isManager)
			{
				//redirect to browse with admonishment
				App::redirect(
					Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
					Lang::txt('PLG_GROUPS_CITATIONS_GROUP_MANAGER_ONLY'),
					'warning'
				);
				return;
			}

			$id = Request::getVar('id', 0);

			if ($id != 0)
			{
				$citation = \Components\Citations\Models\Citation::oneOrFail($id);
				$citation->set('published', $citation::STATE_DELETED);

				if ($citation->save())
				{
					App::redirect(
						Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
						Lang::txt('PLG_GROUPS_CITATIONS_CITATION_DELETED'),
						'success'
					);
					return;
				}
			}
			else
			{
				//error, no such citation
			}
		} //end _delete()

	/**
	 * Settings for group citations
	 *
	 * @param null
	 * @return void
	 *
	 *
	 */
	private function _settings()
	{
		if ($_POST)
		{
			$display = Request::getVar('display', '');
			$format = Request::getVar('citation-format', '');

			$params = json_decode($this->group->get('params'));

			// craft a clever name
			$name =  "custom-group-" . $this->group->cn;

			// fetch or create new format
			$citationFormat = \Components\Citations\Models\Format::oneOrNew($format);

			// if the setting a custom group citation type
			if (($citationFormat->isNew()) || ($citationFormat->style == $name && !$citationFormat->isNew()))
			{
				$citationFormat->set(array(
					'format'		=> Request::getVar('template'),
					'style'			=> $name
				));

				//save format
				$citationFormat->save();

				//update group
				$params->citationFormat = $citationFormat->id;
			}
			else
			{
				// returned value from format select box
				$params->citationFormat = $format;
			}

			// more parameters for citations
			$params->display = Request::getVar('display', '');
			$params->include_coins = Request::getVar('include_coins', '');
			$params->coins_only = Request::getVar('coins_only', '');

			// update the group parameters
			$gParams = new Registry($params);
			$gParams->merge($params);
			$this->group->set('params', $gParams->toString());
			$this->group->update();

			// redirect after save
			App::redirect(
				Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
				Lang::txt('PLG_GROUPS_CITATIONS_SETTINGS_SAVED'),
				'success'
			);
			return;

		}
		else
		{
			//instansiate the view
			$view = $this->view('default', 'settings');

			// pass the group through
			$view->group = $this->group;

			//get group settings
			$params = json_decode($this->group->get('params'));

			$view->include_coins = (isset($params->include_coins) ? $params->include_coins : "false");
			$view->coins_only = (isset($params->coins_only) ? $params->coins_only : "false");
			$citationsFormat = (isset($params->citationFormat) ? $params->citationFormat : 1);

			//get formats
			$view->formats = \Components\Citations\Models\Format::all()->rows()->toObject();
			$view->templateKeys = \Components\Citations\Models\Format::all()->getTemplateKeys();
			$view->currentFormat = \Components\Citations\Models\Format::oneOrFail($citationsFormat);

			// get the name of the current format (see if it's custom)
			// the name of the custom format
			$name = "custom-group-" . $this->group->cn;

			$custom = \Components\Citations\Models\Format::all()->where('style', 'LIKE', $name)->count();
			if ($custom > 0)
			{
				// show the menu entry for the custom
				$view->customFormat = true;
			}
			else
			{
				// show menu item for new custom format
				$view->customFormat = false;
			}

			// Output HTML
			foreach ($this->getErrors() as $error)
			{
				$view->setError($error);
			}

			return $view->loadTemplate();
		}
	}

	/**
	 * Import task 
	 * @return [object] [description]
	 */
	private function _import()
	{
		//instansiate the view
		$view = $this->view('display', 'import');
		$view->group = $this->group;
		$view->messages = NULL;
		$view->accepted_files = Event::trigger('citation.onImportAcceptedFiles' , array());
		$view->isManager = ($this->authorized == 'manager') ? true : false;

		if ($view->isManager == false)
		{
			App::redirect(
			 Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
			 Lang::txt('This function can only be performed by a group manager.'),
			 'warning'
			 );
		}

		return $view->loadTemplate();

	}
	/**
	 * Upload task
	 * @return [object] [description]
	 */
	private function _upload()
	{
		//instansiate the view
		$view = $this->view('review', 'import');
		$view->group = $this->group;
		$view->messages = NULL;

		Request::checkToken();

		// get file
		$file = Request::file('citations_file');

		// make sure we have a file
		$filename = $file->getClientOriginalName();
		if ($filename == '')
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&task=import'),
				Lang::txt('COM_CITATIONS_IMPORT_MISSING_FILE'),
				'error'
			);
			return;
		}

		// make sure file is under 4MB
		if ($file->getSize() > 4000000)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&task=import'),
				Lang::txt('COM_CITATIONS_IMPORT_FILE_TOO_BIG'),
				'error'
			);
			return;
		}

		// make sure we dont have any file errors
		if ($file->getError() > 0)
		{
			throw new Exception(Lang::txt('COM_CITATIONS_IMPORT_UPLOAD_FAILURE'), 500);
		}

		// call the plugins
		$citations = Event::trigger('citation.onImport' , array($file, 'group', $this->group->get('gidNumber')));
		$citations = array_values(array_filter($citations));

		// did we get citations from the citation plugins
		if (!$citations)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&task=import'),
				Lang::txt('COM_CITATIONS_IMPORT_PROCESS_FAILURE'),
				'error'
			);
			return;
		}

		if (!isset($citations[0]['attention']))
		{
			$citations[0]['attention'] = '';
		}

		if (!isset($citations[0]['no_attention']))
		{
			$citations[0]['no_attention'] = '';
		}

		if (!$this->importer->writeRequiresAttention($citations[0]['attention']))
		{
			Notify::error(Lang::txt('Unable to write temporary file.'));
		}

		if (!$this->importer->writeRequiresNoAttention($citations[0]['no_attention']))
		{
			Notify::error(Lang::txt('Unable to write temporary file.'));
		}

		$view->citations_require_attention = $citations[0]['attention'];
		$view->citations_require_no_attention = $citations[0]['no_attention'];

		//get group ID
		$group = Request::getVar('group');

		return $view->loadTemplate();

	}

	/**
	 * Upload task 
	 * @return [object] [description]
	 */
	private function _process()
	{
		//instansiate the view
		$view = $this->view('saved', 'import');
		$view->group = $this->group;
		$view->messages = NULL;
		$config  = new \Hubzero\Config\Registry($this->group->get('params'));

		Request::checkToken();

		$cites_require_attention    = $this->importer->readRequiresAttention();
		$cites_require_no_attention = $this->importer->readRequiresNoAttention();

		// action for citations needing attention
		$citations_action_attention = Request::getVar('citation_action_attention', array());

		// action for citations needing no attention
		$citations_action_no_attention = Request::getVar('citation_action_no_attention', array());

		// check to make sure we have citations
		if (!$cites_require_attention && !$cites_require_no_attention)
		{
			App::redirect(
				Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations&action=import'),
				Lang::txt('COM_CITATIONS_IMPORT_MISSING_FILE_CONTINUE'),
				'error'
			);
			return;
		}

		// vars
		$allow_tags   = "yes";
		$allow_badges = "yes";

		//var_dump($this->group->get('gidNumber')); die; 
		$this->importer->set('user', User::get('id'));
		$this->importer->set('scope', 'group');
		$this->importer->set('scope_id', $this->group->get('gidNumber'));
		$this->importer->setTags($allow_tags == 'yes');
		$this->importer->setBadges($allow_badges == 'yes');

		// Process
		$results = $this->importer->process(
			$citations_action_attention,
			$citations_action_no_attention
		);

		if (isset($group) && $group != '')
		{
			require_once(PATH_CORE . DS . 'components' . DS . 'com_groups' . DS . 'tables' . DS . 'group.php');
			$gob = new \Components\Groups\Tables\Group($this->database);
			$cn = $gob->getName($group);

			App::redirect(
				Route::url('index.php?option=com_groups&cn=' . $cn . '&active=citations&action=dashboard')
			);
		}
		else
		{
			// success message a redirect
			Notify::success(
				Lang::txt('COM_CITATIONS_IMPORT_RESULTS_SAVED', count($results['saved'])),
				'citations'
			);

			//view variables
			$view->citations_require_attention = (isset($citations_action_attention) ? $citations_action_attention : null);
			$view->citation_require_no_attention = (isset($citation_action_no_attention) ?  $citations_action_no_attention : null);

			// if we have citations not getting saved
			if (count($results['not_saved']) > 0)
			{
				Notify::warning(
					Lang::txt('COM_CITATIONS_IMPORT_RESULTS_NOT_SAVED', count($results['not_saved'])),
					'citations'
				);
			}

			if (count($results['error']) > 0)
			{
				Notify::error(
					Lang::txt('COM_CITATIONS_IMPORT_RESULTS_SAVE_ERROR', count($results['error'])),
					'citations'
				);
			}

			//get the session object
			$session = App::get('session');

			//ids of sessions saved and not saved
			$session->set('citations_saved', $results['saved']);
			$session->set('citations_not_saved', $results['not_saved']);
			$session->set('citations_error', $results['error']);

			//delete the temp files that hold citation data
			$this->importer->cleanup(true);

			// redirect after save
			App::redirect(
				Route::url('index.php?option=com_groups&cn=' . $this->group->cn . '&active=citations'),
				Lang::txt('PLG_GROUPS_CITATIONS_CITATION_SAVED'),
				'success'
			);
			return;
	 }
}

	 /**
	 * Redirect to login form
	 *
	 * @return	void
	 */
	private function _loginTask()
	{
		App::redirect(
			Route::url('index.php?option=com_users&view=login&return=' . base64_encode(Route::url('index.php?option=' . $this->option . DS . $this->group->get('cn') . DS. $this->_name .'&action=' . $this->action, false, true))),
			Lang::txt('PLG_GROUPS_CITATIONS_NOT_LOGGEDIN'),
			'warning'
		);
		return;
	}

	/**
	 * Uses URL to determine OpenURL server
	 *
	 * @return	object $openURL
	 */
	private function _handleOpenURL()
	{
		//get the users id to make lookup
		$users_ip = Request::ip();

		//get the param for ip regex to use machine ip
		$ip_regex = array('10.\d{2,5}.\d{2,5}.\d{2,5}');

		$use_machine_ip = false;
		foreach ($ip_regex as $ipr)
		{
			$match = preg_match('/' . $ipr . '/i', $users_ip);
			if ($match)
			{
				$use_machine_ip = true;
			}
		}

		//make url based on if were using machine ip or users
		if ($use_machine_ip)
		{
			$url = 'http://worldcatlibraries.org/registry/lookup?IP=' . $_SERVER['SERVER_ADDR'];
		}
		else
		{
			$url = 'http://worldcatlibraries.org/registry/lookup?IP=' . $users_ip;
		}

		//get the resolver
		$r = null;
		if (function_exists('curl_init'))
		{
			$cURL = curl_init();
			curl_setopt($cURL, CURLOPT_URL, $url );
			curl_setopt($cURL, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($cURL, CURLOPT_TIMEOUT, 10);
			$r = curl_exec($cURL);
			curl_close($cURL);
		}

		//parse the returned xml
		$openurl = array(
			'link' => '',
			'text' => '',
			'icon' => ''
		);

		//parse the return from resolver lookup
		$resolver = null;
		$xml = simplexml_load_string($r);
		if (isset($xml->resolverRegistryEntry))
		{
			$resolver = $xml->resolverRegistryEntry->resolver;
		}

		//if we have resolver set vars for creating open urls
		if ($resolver != null)
		{
			$openURL['link'] = $resolver->baseURL;
			$openURL['text'] = $resolver->linkText;
			$openURL['icon'] = $resolver->linkIcon;

			return $openURL;
		}

		return false;
	}

	/**
	 * Applies filters to Citations model and returns applied filters
	 * @param array  $filters array of POST values
	 * @return	array sanitized and validated filter values
	 */
	private function _filterHandler($filters = array(),  $scope_id = 0)
	{
		$citations = \Components\Citations\Models\Citation::all();
		// require citations
		if (!$citations)
		{
			return false;
		}

		// get the ones for this group
		$scope = 'group';

		$citations->where('scope', '=', $scope);
		$citations->where('scope_id', '=', $scope_id);
		$citations->where('published', '!=', $citations::STATE_DELETED); // don't include deleted citations

		if (count($filters) > 0)
		{
			foreach ($filters as $filter => $value)
			{
				// sanitization
				$value = \Hubzero\Utility\Sanitize::clean($value);

				// we handle things differently in search and sorting
				if ($filter != 'search' && $filter != 'sort' && $filter!= 'tag' && $value != "")
				{
					if ($filter == 'author')
					{
						$citations->where('author', 'LIKE', "%{$value}%", 'and', 1);
					}
					elseif ($filter == 'publishedin')
					{
						$citations->where('date_publish', 'LIKE', "%{$value}-%");
					}
					elseif ($filter == 'year_start')
					{
						$citations->where('year', '>=', $value);
					}
					elseif ($filter == 'year_end')
					{
						$citations->where('year', '<=', $value);
					}
					else
					{
						$citations->where($filter, '=', $value);
					}
				} //end if not search & not sort & non-empty value

				// for searching
				if ($filter == "search" && $value != "")
				{
					$terms = preg_split('/\s+/', $value);

					$value = \Hubzero\Utility\Sanitize::clean($value);
					$term = $value;
					$collection = array();
					$columns = array('author', 'title', 'isbn', 'doi', 'publisher', 'abstract');
					foreach ($columns as $column)
					{
						foreach ($terms as $term)
						{
							// copy the original item
							$cite = clone $citations;

							// do some searching
							$cite->where($column, 'LIKE', "%{$term}%");

							foreach ($cite as $c)
							{
								// put for collection later
								array_push($collection, $c->id);
							} //end foreach $cite
						} //end foreach terms
					} //end foreach columns

					// remove duplicates
					$collection = array_unique($collection);

					// pull the appropriate ones.
					$citations->whereIn('id', $collection);
			} //end searching

			// for tags
			if ($filter == "tag" && $value != "")
			{
				$collection = array();
				$cite = clone $citations;
				foreach ($cite as $c)
				{
					foreach ($c->tags as $tag)
					{
						if ($tag->tag == $value)
						{
							array_push($collection, $c->id);
						}
					}
				}

				//remove duplicates
				$collection = array_unique($collection);

				//get the tagged ones
				$citations->whereIn('id', $collection);
		 } // end if tags

			if ($filter == "sort" && $value != "")
			{
				$clause = explode(" ", $value);
				$citations->order($clause[0], $clause[1]);
			}
		} //end foreach filters as filter

			return array('citations' => $citations, 'filters' => $filters);
		}
		else
		{
			return array('citations' => $citations, 'filters' => array());
		}
	}

	/**
	 * Return a list of citations for a specific user
	 *
	 * @param	object	$group		Current group
	 * @param	object	$profile	USer profile
	 * @return	string
	 */
	public function onGroupMemberAfter($group, $profile)
	{
		$view = $this->view('default', 'member');
		$view->group	= $group;
		$view->option	= 'com_groups';
		$view->task		= $this->_name;
		$view->database = App::get('db');
		$view->title	= Lang::txt(strtoupper($this->_name));

		$view->citationTemplate = 'apa';

		$view->filters['search'] = "";
		$view->filters['type'] = '';
		$view->filters['tag'] = '';
		$view->filters['author'] = '';
		$view->filters['publishedin'] = '';
		$view->filters['year_start'] = '';
		$view->filters['year_end'] = '';
		$view->filters['startuploaddate'] = '';
		$view->filters['enduploaddate'] = '';
		$view->filters['sort'] = '';

		// get the preferred labeling scheme
		$view->label = null;

		switch ($view->label)
		{
			case 'none':
				$view->citations_label_class = 'no-label';
			break;
			case 'number':
				$view->citations_label_class = 'number-label';
			break;
			case 'type':
				$view->citations_label_class = 'type-label';
			break;
			case 'both':
			default:
				$view->citations_label_class = 'both-label';
			break;
		}

		// enable coins support
		$view->coins = 1;

		// types
		$view->types = \Components\Citations\Models\Type::all();

		// OpenURL
		$openURL = $this->_handleOpenURL();
		$view->openurl['link'] = $openURL['link'];
		$view->openurl['text'] = $openURL['text'];
		$view->openurl['icon'] = $openURL['icon'];

		$view->citations = array();

		return $view->loadTemplate();
	}
}
