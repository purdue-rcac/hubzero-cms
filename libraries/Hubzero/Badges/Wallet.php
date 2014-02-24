<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2014 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Badges;

use Hubzero\Badges\Provider\ProviderInterface;
use Hubzero\Badges\Exception\InvalidProviderException;
use Hubzero\Badges\Exception\ProviderNotFoundException;

/**
 * Hubzero badges class
 */
class Wallet
{
	/**
	 * Badge provider
	 *
	 * @var object
	 */
	private $_provider;

	/**
	 * Constructor
	 * 
	 * @param	string 		provider
	 * @param	string 		requestType
	 * @return  void
	 */
	public function __construct($provider, $requestType='oauth')
	{
		$cls = __NAMESPACE__ . '\\Provider\\' . ucfirst(strtolower($provider));

		if (!class_exists($cls))
		{
			throw new ProviderNotFoundException(\JText::sprntf('Invalid badges provider of "%s".', $provider));
		}

		$this->_provider = new $cls($requestType);

		if (!($this->_provider instanceof ProviderInterface))
		{
			throw new InvalidProviderException(\JText::sprintf('Invalid badges provider of "%s". Provider must implement ProviderInterface', $provider));
		}
	}

	/**
	 * Get badges provider instance
	 *
	 */
	public function getProvider()
	{
		return $this->_provider;
	}
}