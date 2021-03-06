<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief       BitTracker Main Controller
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	BitTracker
 * @version     2.5.0 Stable
 * @source      https://github.com/devCU/IPS-BitTracker
 * @Issue Trak  https://www.devcu.com/forums/devcu-tracker/
 * @Created     11 FEB 2018
 * @Updated     24 OCT 2020
 *
 *                       GNU General Public License v3.0
 *    This program is free software: you can redistribute it and/or modify       
 *    it under the terms of the GNU General Public License as published by       
 *    the Free Software Foundation, either version 3 of the License, or          
 *    (at your option) any later version.                                        
 *                                                                               
 *    This program is distributed in the hope that it will be useful,            
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of             
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *                                                                               
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see http://www.gnu.org/licenses/
 */

namespace IPS\bitracker\modules\front\portal;

 /* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Main Controller
 */
class _main extends \IPS\Dispatcher\Controller
{

	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{

		if( \IPS\Settings::i()->bit_breadcrumb_name_enable )
		{
		\IPS\Output::i()->breadcrumb	= array();
		\IPS\Output::i()->breadcrumb['module'] = array( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main', 'front', 'bitracker' ), \IPS\Settings::i()->bit_breadcrumb_name );
        }
       else
        {
		\IPS\Output::i()->breadcrumb	= array();
		\IPS\Output::i()->breadcrumb['module'] = array( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main', 'front', 'bitracker' ), \IPS\Settings::i()->bit_application_name );
        }

		parent::execute();
}

	/**
	 * Mark Read
	 *
	 * @return	void
	 */
	protected function markRead()
	{
		\IPS\Session::i()->csrfCheck();
		
		try
		{
			$category	= \IPS\bitracker\Category::load( \IPS\Request::i()->id );

			\IPS\bitracker\File::markContainerRead( $category, NULL, FALSE );

			\IPS\Output::i()->redirect( $category->url() );
		}
		catch ( \OutOfRangeException $e )
		{
			\IPS\Output::i()->error( 'no_module_permission', '2D175/3', 403, 'no_module_permission_guest' );
		}
	}

	/**
	 * Route
	 *
	 * @return	void
	 */
	protected function manage()
	{
		if ( isset( \IPS\Request::i()->currency ) and \in_array( \IPS\Request::i()->currency, \IPS\nexus\Money::currencies() ) and isset( \IPS\Request::i()->csrfKey ) and \IPS\Request::i()->csrfKey === \IPS\Session\Front::i()->csrfKey )
		{
			\IPS\Request::i()->setCookie( 'currency', \IPS\Request::i()->currency );
		}
		
		if ( isset( \IPS\Request::i()->id ) )
		{
			if ( \IPS\Request::i()->id == 'clubs' and \IPS\Settings::i()->club_nodes_in_apps )
			{
				\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&do=categories', 'front', 'bitracker_categories' ), array(), 'loc_bitracker_browsing_categories' );
				\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&do=categories', 'front', 'bitracker_categories' ), \IPS\Member::loggedIn()->language()->addToStack('bitracker_categories') );
				\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&id=clubs', 'front', 'bitracker_clubs' ), \IPS\Member::loggedIn()->language()->addToStack('club_node_bitracker') );
				\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('club_node_bitracker');
				\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->categories( TRUE );
			}
			else
			{
				try
				{
					$this->_category( \IPS\bitracker\Category::loadAndCheckPerms( \IPS\Request::i()->id, 'read' ) );
				}
				catch ( \OutOfRangeException $e )
				{
					\IPS\Output::i()->error( 'node_error', '2D175/1', 404, '' );
				}
			}
		}
		else
		{
			$this->_index();
		}
	}
	
	/**
	 * Show Index
	 *
	 * @return	void
	 */
	protected function _index()
	{
		/* Add RSS feed */
		if ( \IPS\Settings::i()->bit_rss )
		{
			\IPS\Output::i()->rssFeeds['bit_rss_title'] = \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&do=rss', 'front', 'bitracker_rss' );

			if ( \IPS\Member::loggedIn()->member_id )
			{
				$key = md5( ( \IPS\Member::loggedIn()->members_pass_hash ?: \IPS\Member::loggedIn()->email ) . \IPS\Member::loggedIn()->members_pass_salt );

				\IPS\Output::i()->rssFeeds['bit_rss_title'] = \IPS\Output::i()->rssFeeds['bit_rss_title']->setQueryString( array( 'member' => \IPS\Member::loggedIn()->member_id , 'key' => $key ) );
			}
		}
		
		/* Get stuff */
		$featured = \IPS\Settings::i()->bit_show_featured ? iterator_to_array( \IPS\bitracker\File::featured( \IPS\Settings::i()->bit_featured_count, '_rand' ) ) : array();

		if ( \IPS\Settings::i()->bit_newest_categories )
		{
			$newestWhere = array( array( 'bitracker_categories.copen=1 and ' . \IPS\Db::i()->in('file_cat', explode( ',', \IPS\Settings::i()->bit_newest_categories ) ) ) );
		}
		else
		{
			$newestWhere = array( array( 'bitracker_categories.copen=1' ) );
		}
		if ( !\IPS\Settings::i()->club_nodes_in_apps )
		{
			$newestWhere[] = array( 'bitracker_categories.cclub_id IS NULL' );
		}

        $new = ( \IPS\Settings::i()->bit_show_newest) ? \IPS\bitracker\File::getItemsWithPermission( $newestWhere, NULL, 14, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();

		if (\IPS\Settings::i()->bit_highest_rated_categories )
		{
			$highestWhere = array( array( 'bitracker_categories.copen=1 and ' . \IPS\Db::i()->in('file_cat', explode( ',', \IPS\Settings::i()->bit_highest_rated_categories ) ) ) );
		}
		else
		{
			$highestWhere = array( array( 'bitracker_categories.copen=1' ) );
		}
		$highestWhere[] = array( 'file_rating > ?', 0 );
		if ( !\IPS\Settings::i()->club_nodes_in_apps )
		{
			$highestWhere[] = array( 'bitracker_categories.cclub_id IS NULL' );
		}
		$highestRated = ( \IPS\Settings::i()->bit_show_highest_rated ) ? \IPS\bitracker\File::getItemsWithPermission( $highestWhere, 'file_rating DESC, file_reviews DESC', 14, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();

		if (\IPS\Settings::i()->bit_show_most_downloaded_categories )
		{
			$mostDownloadedWhere = array( array( 'bitracker_categories.copen=1 and ' . \IPS\Db::i()->in('file_cat', explode( ',', \IPS\Settings::i()->bit_show_most_downloaded_categories ) ) ) );
		}
		else
		{
			$mostDownloadedWhere = array( array( 'bitracker_categories.copen=1' ) );
		}
		$mostDownloadedWhere[] = array( 'bitracker_categories.copen=1 and file_torrents > ?', 0 );
		if ( !\IPS\Settings::i()->club_nodes_in_apps )
		{
			$mostDownloadedWhere[] = array( 'bitracker_categories.cclub_id IS NULL' );
		}
		$mostDownloaded = ( \IPS\Settings::i()->bit_show_most_downloaded ) ? \IPS\bitracker\File::getItemsWithPermission( $mostDownloadedWhere, 'file_torrents DESC', 14, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, TRUE ) : array();
		
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=bitracker', 'front', 'bitracker' ), array(), 'loc_bitracker_browsing' );
		
		/* Display */
		\IPS\Output::i()->sidebar['contextual'] = \IPS\Theme::i()->getTemplate( 'browse' )->indexSidebar( \IPS\bitracker\Category::canOnAny('add') );
		\IPS\Output::i()->title		= \IPS\Settings::i()->bit_application_name;
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->index( $featured, $new, $highestRated, $mostDownloaded );
	}
	
	/**
	 * Show Category
	 *
	 * @param	\IPS\bitracker\Category	$category	The category to show
	 * @return	void
	 */
	protected function _category( $category )
	{
		$category->clubCheckRules();
		
		\IPS\Output::i()->sidebar['contextual'] = '';
		
		$_count = \IPS\bitracker\File::getItemsWithPermission( array( array( \IPS\bitracker\File::$databasePrefix . \IPS\bitracker\File::$databaseColumnMap['container'] . '=?', $category->_id ) ), NULL, 1, 'read', \IPS\Content\Hideable::FILTER_AUTOMATIC, 0, NULL, FALSE, FALSE, FALSE, TRUE );

		if( !$_count )
		{
			/* If we're viewing a club, set the breadcrumbs appropriately */
			if ( $club = $category->club() )
			{
				$club->setBreadcrumbs( $category );
			}
			else
			{
				foreach ( $category->parents() as $parent )
				{
					\IPS\Output::i()->breadcrumb[] = array( $parent->url(), $parent->_title );
				}
				\IPS\Output::i()->breadcrumb[] = array( NULL, $category->_title );
			}

			/* Show a 'no files' template if there's nothing to display */
			$table = \IPS\Theme::i()->getTemplate( 'browse' )->noFiles( $category );
		}
		else
		{
			/* Build table */
			$table = new \IPS\Helpers\Table\Content( 'IPS\bitracker\File', $category->url(), NULL, $category );
			$table->classes = array( 'ipsDataList_large' );
			$table->sortOptions = array_merge( $table->sortOptions, array( 'file_torrents' => 'file_torrents' ) );

			if ( !$category->bitoptions['reviews_bitrack'] )
			{
				unset( $table->sortOptions['num_reviews'] );
			}

			if ( !$category->bitoptions['comments'] )
			{
				unset( $table->sortOptions['last_comment'] );
				unset( $table->sortOptions['num_comments'] );
			}

			if ( $table->sortBy === 'bitracker_torrents.file_title' )
			{
				$table->sortDirection = 'asc';
			}
			
			if ( \IPS\Application::appIsEnabled( 'nexus' ) and \IPS\Settings::i()->bit_nexus_on )
			{
				$table->filters = array(
					'file_free'	=> "( ( file_cost='' OR file_cost IS NULL ) AND ( file_nexus='' OR file_nexus IS NULL ) )",
					'file_paid'	=> "( file_cost<>'' OR file_nexus>0 )",
				);
			}
			$table->title = \IPS\Member::loggedIn()->language()->pluralize(  \IPS\Member::loggedIn()->language()->get('bitrack_file_count'), array( $_count ) );
		}

		/* Online User Location */
		$permissions = $category->permissions();
		\IPS\Session::i()->setLocation( $category->url(), explode( ",", $permissions['perm_view'] ), 'loc_bitracker_viewing_category', array( "bitracker_category_{$category->id}" => TRUE ) );
				
		/* Output */
		\IPS\Output::i()->title		= $category->_title;
		\IPS\Output::i()->contextualSearchOptions[ \IPS\Member::loggedIn()->language()->addToStack( 'search_contextual_item_bitracker_categories' ) ] = array( 'type' => 'bitracker_torrent', 'nodes' => $category->_id );
		\IPS\Output::i()->sidebar['contextual'] .= \IPS\Theme::i()->getTemplate( 'browse' )->indexSidebar( \IPS\bitracker\Category::canOnAny('add'), $category );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->category( $category, (string) $table );
	}

	/**
	 * Show a category listing
	 *
	 * @return	void
	 */
	protected function categories()
	{
		/* Online User Location */
		\IPS\Session::i()->setLocation( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&do=categories', 'front', 'bitracker_categories' ), array(), 'loc_bitracker_browsing_categories' );
		
		\IPS\Output::i()->title		= \IPS\Member::loggedIn()->language()->addToStack('bitracker_categories_pagetitle');
		\IPS\Output::i()->breadcrumb[] = array( \IPS\Http\Url::internal( 'app=bitracker&module=portal&controller=main&do=categories', 'front', 'bitracker_categories' ), \IPS\Member::loggedIn()->language()->addToStack('bitracker_categories') );
		\IPS\Output::i()->output	= \IPS\Theme::i()->getTemplate( 'browse' )->categories();
	}
}