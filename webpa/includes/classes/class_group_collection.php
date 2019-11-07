<?php
/**
 * Class :  GroupCollection
 *
 * Applications using GroupCollection should call ->lock() once a group has been used
 * to prevent any changes being made to the group from then on.
 *
 * Once locked, the GroupCollection will not allow SAVING or DELETING, or MEMBER editing
 *
 * @copyright Loughborough University
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL version 3
 *
 * @link https://github.com/webpa/webpa
 */

require_once('class_dao.php');
require_once('class_group.php');

class GroupCollection {
  // Public Vars
  public $id = null;
  public $module_id = null;
  public $name = '';

  // Private Vars
  private $_DAO = null;

  private $_groups = null;
  private $_group_objects = null;

  private $_created_on = null;
  private $_locked_on = null;

  private $_assessment_id = null;

  /**
  * CONSTRUCTOR for the Group collection function
  */
  function __construct(&$DAO) {
    $this->_DAO =& $DAO;
    $this->_created_on = time();
    $this->_groups = null;
    $this->_group_objects = null;
    $this->_assessment_id = null;
  }// /->GroupCollection()

/*
* ================================================================================
* Public Methods
* ================================================================================
*/

/*
* --------------------------------------------------------------------------------
* Load/Save Functions
* --------------------------------------------------------------------------------
*/

  /**
   *  Create a new GroupCollection (generates unique collection_id)
  */
  function create() {
    while (true) {
      $new_id = uuid_create();
      if ($this->_DAO->fetch_value("SELECT COUNT(collection_id) AS num_id FROM " . APP__DB_TABLE_PREFIX . "collection WHERE collection_id = '$new_id' ") == 0) { break; }
    }
    $this->id = $new_id;
  }// ->create()

  /**
  * Load the GroupCollection from the database
  *
  * @param string $id  id of GroupCollection to load
  *
  * @return boolean did load succeed
  */
  function load($id) {
    $row = $this->_DAO->fetch_row("SELECT c.*, a.assessment_id AS collection_assessment_id
       FROM " . APP__DB_TABLE_PREFIX . "collection c
         LEFT OUTER JOIN " . APP__DB_TABLE_PREFIX . "assessment a ON c.collection_id = a.collection_id
       WHERE c.collection_id = '$id'");
    return ($row) ? $this->load_from_row($row) : false;
  }// /->load()

  /**
  * Load the GroupCollection from an array row
  *
  * @param array $row assoc array of ( field => value ) - corresponds to row in database
  *
  * @return boolean
  */
  function load_from_row(&$row) {
    $this->id = $row['collection_id'];
    $this->module_id = $row['module_id'];
    $this->name = $row['collection_name'];
    $this->_created_on  = strtotime($row['collection_created_on']);
    $this->_locked_on = ( (is_null($row['collection_locked_on'])) ? null : strtotime($row['collection_locked_on']) );
    $this->_assessment_id = $row['collection_assessment_id'];
    return true;
  }// /->load_from_row()

  /**
  * Delete this GroupCollection (and all its groups, members, and module links)
  * If the collection is locked, the delete will fail
  *
  * @retutn  boolean did deletion succeed
  */
  function delete() {
    if ($this->is_locked()) {
      return false;
    } else {
      $this->_DAO->execute("DELETE FROM " . APP__DB_TABLE_PREFIX . "user_group_member WHERE group_id IN (SELECT group_id FROM " . APP__DB_TABLE_PREFIX . "user_group WHERE collection_id = '{$this->id}')");
      $this->_DAO->execute("DELETE FROM " . APP__DB_TABLE_PREFIX . "user_group WHERE collection_id = '{$this->id}'");
      $this->_DAO->execute("DELETE FROM " . APP__DB_TABLE_PREFIX . "collection WHERE collection_id = '{$this->id}'");
      return true;
    }
  }// /->delete()

  /**
  * Save this GroupCollection
  *
  * @return boolean did save succeed
  */
  function save() {
    if ( (!$this->id) || ($this->is_locked()) ) {
      return false;
    } else {
      // load all necessary info
      $fields = array ('collection_id'      => $this->id ,
               'module_id'      => $this->module_id ,
               'collection_name'      => $this->name ,
               'collection_created_on'  => date(MYSQL_DATETIME_FORMAT,$this->_created_on) ,
               'collection_locked_on'   => ( (!$this->_locked_on) ? null : date(MYSQL_DATETIME_FORMAT,$this->_locked_on) ) ,
              );

      // Save this collection
      $sql = 'INSERT ' . APP__DB_TABLE_PREFIX . 'collection ({fields}) VALUES ({values}) ' .
         "ON DUPLICATE KEY UPDATE module_id = {$fields['module_id']}, collection_name = '{$fields['collection_name']}', collection_created_on = '{$fields['collection_created_on']}', ";
      if (is_null($fields['collection_locked_on'])) {
        $sql .= 'collection_locked_on = NULL';
      } else {
        $sql .= "collection_locked_on = '{$fields['collection_locked_on']}'";
      }
      $this->_DAO->do_insert($sql, $fields);

      return true;
    }
  }// /->save()

  /**
  * Save open Group objects attached to this GroupCollection
  * Loops through the group objects opened through the GroupCollection and saves any changes
  */
  function save_groups() {
    if (is_array($this->_group_objects)) {
      foreach ($this->_group_objects as $group_id => $group) {
        if (is_object($this->_group_objects[$group_id])) { $this->_group_objects[$group_id]->save(); }
      }
    }
  }// /->save_groups()

/*
* --------------------------------------------------------------------------------
* Accessor Methods
* --------------------------------------------------------------------------------
*/

  /**
  * Set the GroupCollection's owner info
  */
  function set_owner_info($id, $type) {
    $this->_assessment_id = null;
    switch ($type) {
      case APP__COLLECTION_USER:
        break;
      case APP__COLLECTION_ASSESSMENT:
        $this->_assessment_id = $id;
        break;
    }
  }// /->set_owner_info()

  /**
  * Is this GroupCollection locked?
  *
  * @return boolean is the collection locked
  */
  function is_locked() {
    return ( (!is_null($this->_locked_on)) || ($this->_locked_on) ) ;
  }// /->is_locked()

/*
* --------------------------------------------------------------------------------
* Other Methods
* --------------------------------------------------------------------------------
*/

  /**
  * Lock the collection - should prevent applications editing/deleting the groups involved
  * The locked_on datetime is IMMEDIATELY SAVED to the database (no other fields are saved)
  */
  function lock() {
    $this->_locked_on = time();
    if ($this->id) {
      $_fields = array  (
                          'collection_locked_on'  => date(MYSQL_DATETIME_FORMAT,$this->_locked_on) ,
                        );
      $this->_DAO->do_update("UPDATE " . APP__DB_TABLE_PREFIX . "collection ({fields}) WHERE collection_id = '{$this->id}' ",$_fields);
    }
  }// /->lock()

/*
* --------------------------------------------------------------------------------
* Group Manipulation Methods
* --------------------------------------------------------------------------------
*/

/*
* ----------------------------------------
* These methods only manipulate the group data
* ----------------------------------------
*/

  /**
  * Returns an array of groups belonging to this GroupCollection
  *
  * @return assoc array ( group_name => group_id )
  */
  function get_groups_array() {
    if (!$this->_groups) { $this->refresh_groups(); }
    return $this->_groups;
  }// /->get_groups_array()

  /**
  * Check if there is a group in this GroupCollection with the given name
  * @param string $group_name
  * @return boolean
  */
  function group_exists($group_name) {
    if (!$this->_groups) { $this->refresh_groups(); }

    $is_valid_group = false;
    foreach($this->_groups as $i => $group_row) {
      if ($group_row['group_name'] == $group_name) {
        $is_valid_group = true;
        break;
      }
    }

    return $is_valid_group;
  }// /->group_exists()

  /**
   * Check if the group exists
   * @param mixed $group_id
   * @return bool  does the group exist in this collection
  */
  function group_id_exists($group_id) {
    if (!$this->_groups) { $this->refresh_groups(); }

    $is_valid_group = false;
    foreach($this->_groups as $i => $group_row) {
      if ($group_row['group_id']==$group_id) { $is_valid_group = true; break; }
    }

    return $is_valid_group;
  }// /->group_id_exists()

  /**
  * Refresh this collection's list of groups
  */
  function refresh_groups() {
    $this->_groups = $this->_DAO->fetch("
      SELECT *
      FROM " . APP__DB_TABLE_PREFIX . "user_group
      WHERE collection_id = '{$this->id}'
      ORDER BY group_name ASC
    ");
    if (!$this->_groups) { $this->_groups = array(); }
    uasort($this->_groups, array('GroupCollection', 'group_title_natural_sort'));
  }// /->refresh_groups()
  private static function group_title_natural_sort($group_a, $group_b) {
    return strnatcmp($group_a['group_name'], $group_b['group_name']);
  }

/*
* ----------------------------------------
* These methods manipulate the group objects
* ----------------------------------------
*/

  /**
  * Add the given Group object to the GroupCollection object
  * If the Group's ID already exists, the existing reference will be replaced
  *
  * @param object $group_object The group to add
  */
  function add_group_object(&$group_object) {
    if (!is_array($this->_groups)) { $this->refresh_groups(); }

    if (is_object($group_object)) {
      $this->_groups[$group_object->id] = $group_object->get_as_array();
      $this->_group_objects["{$group_object->id}"] =& $group_object;
      $group_object->set_collection_object($this);
    }
  }// /->add_group_object()

  /**
  * Get the group object corresponding to the given group_id
  * If you want to get a list of all the objects, use ->get_group_iterator() instead
  *
  * @param string $group_id Group ID to fetch
  *
  * @return object Group object (or NULL)
  */
  function & get_group_object($group_id) {
    if (!is_array($this->_groups)) { $this->refresh_groups(); }

    // If this group exists in this collection
    if ($this->group_id_exists($group_id)) {
      // If we already have a copy of the Group object, return it
      if ( (array_key_exists($group_id, (array) $this->_group_objects)) && (is_object($this->_group_objects[$group_id])) ) {
        return $this->_group_objects[$group_id];
      } else {
        $new_group = new Group();
        $new_group->set_dao_object($this->_DAO);
        $new_group->set_collection_object($this);
        $new_group->load($group_id);
        $this->_group_objects[$group_id] =& $new_group;
        return $new_group;
      }
    }
    return null;
  }// /->get_group_object()

  /**
  * Create a new Group object using this GroupCollection as the parent
  * Adds the new Group to the GroupCollection's group list
  *
  * @param string $group_name Name of new group to add
  * @return array
  */
  function & new_group($group_name = 'new group') {
    $new_group = new Group();
    $new_group->set_dao_object($this->_DAO);
    $new_group->create();
    $new_group->name = $group_name;
    $new_group->set_collection_object($this);

    $this->_group[$new_group->id] = $new_group->get_as_array();
    $this->_group_objects[$new_group->id] =& $new_group;
    return $new_group;
  }// /->new_group()

  /**
  * Get an iterator object containg the groups belonging to this collection
  *
  * @return object GroupIterator object
  */
  function & get_groups_iterator() {
    if (!$this->_groups) { $this->refresh_groups(); }

    require_once('class_simple_iterator.php');
    foreach($this->_groups as $i => $group_row) {
      $this->get_group_object($group_row['group_id']);
    }
    $iterator = new SimpleIterator( $this->_group_objects );
    return $iterator;
  }// /->get_groups_iterator()

/*
* --------------------------------------------------------------------------------
* Member-Manipulation Methods
* --------------------------------------------------------------------------------
*/

  /**
  * Get a count of the members in this collection
  *
  * @param string $role  (optional) user role to search for
  *
  * @return integer
  */
  function get_member_count($role = null) {
    return $this->_DAO->fetch_value("SELECT COUNT(ugm.user_id)
                    FROM " . APP__DB_TABLE_PREFIX . "user_group_member ugm
                      INNER JOIN " . APP__DB_TABLE_PREFIX . "user_group ug ON ugm.group_id = ug.group_id
                    WHERE ug.collection_id = '{$this->id}'
                    ");
  }// /->get_member_count()

  /**
  * Get a count of the members in this collection by group
  *
  * @param string $role (optional) user role to search for
  *
  * @return array  array ( group_id => member_count )
  */
  function get_member_count_by_group($role = null) {
    return $this->_DAO->fetch_assoc("SELECT ugm.group_id, COUNT(user_id)
                    FROM " . APP__DB_TABLE_PREFIX . "user_group_member ugm
                      INNER JOIN " . APP__DB_TABLE_PREFIX . "user_group ug ON ugm.group_id = ug.group_id
                    WHERE ug.collection_id = '{$this->id}'
                    GROUP BY ugm.group_id
                    ORDER BY ugm.group_id");
  }// /->get_member_count_by_group()

  /**
  * Get the members actually contained within this collection's groups
  *
  * @param string $role   (optional) user role to search for
  *
  * @return array - assoc array ( user_id => user_role )
  */
  function get_members($role = null) {
    return $this->_DAO->fetch_assoc("SELECT ugm.user_id, 'member' AS user_role
                    FROM " . APP__DB_TABLE_PREFIX . "user_group_member ugm
                      INNER JOIN " . APP__DB_TABLE_PREFIX . "user_group ug ON ugm.group_id = ug.group_id
                    WHERE ug.collection_id = '{$this->id}'
                    ORDER BY ugm.user_id ASC");
  }// /->get_members()

  /**
  * Get row data for this collection's members
  *
  * @return array - array ( group_id, user_id, user_role )
  */
  function get_member_rows() {
    return $this->_DAO->fetch("SELECT ugm.group_id, ugm.user_id
                  FROM " . APP__DB_TABLE_PREFIX . "user_group_member ugm
                    INNER JOIN " . APP__DB_TABLE_PREFIX . "user_group ug ON ugm.group_id = ug.group_id
                  WHERE ug.collection_id = '{$this->id}'
                  ORDER BY ugm.group_id ASC");
  }// /->get_member_rows()

  /**
  * Get group objects for all the groups the given member belongs to
  *
  * @param string $user_id   user id of the member
  *
  * @return array array of group objects
  */
  function & get_member_groups($user_id) {
    $member_roles = $this->get_member_roles($user_id);
    if ($member_roles) {
      $groups = null;
      foreach($member_roles as $group_id => $role) {
        $groups[] =& $this->get_group_object($group_id);
      }
      return $groups;
    }
  }// /->get_member_groups()

  /**
  * Get a user's roles for each group in this collection
  *
  * @param string $user_id   user to search for
  *
  * @return array - assoc array ( group_id => user_role );
  */
  function get_member_roles($user_id) {
    return $this->_DAO->fetch_assoc("SELECT ugm.group_id, 'member' AS user_role
                    FROM " . APP__DB_TABLE_PREFIX . "user_group_member ugm
                      INNER JOIN " . APP__DB_TABLE_PREFIX . "user_group ug ON ugm.group_id = ug.group_id
                    WHERE ug.collection_id = '{$this->id}' AND ugm.user_id = $user_id
                    ORDER BY ugm.group_id ASC");
  }// /->get_member_roles()

  /**
  * Purge a collection of its members using include/exclude lists
  * Due to the way the lists work, you need only use one of them at a time
  *
  * @param string $target_roles  single role to purge (optional)
  *                   (array) - array of roles to purge
  *                   If a $target_roles list is given (not null), and a user's role is not in it, they are kept regardless of the $protect_list
  *                   If a user's role is in the $target_roles list, they are removed
  *
  * @param string $protect_roles   single role to keep (optional)
  *                   (array) - array of roles to keep
  *                   If a user's role is in the $protect_roles list, they are kept
  */
  function purge_members($target_roles = null, $protect_roles = null) {
    if ($this->is_locked()) { return false; }

    $groups_iterator = $this->get_groups_iterator();
    if ($groups_iterator->size()>0) {
      for ($groups_iterator->reset(); $groups_iterator->is_valid(); $groups_iterator->next()) {
        $group =& $groups_iterator->current();
        $group->purge_members($target_roles, $protect_roles);
      }
    }
  }// /->purge_members()

  /**
  * Add member to the collection
  * Adds the given user immediately to the database
  *
  * @param string $user_id  The ID of the user to add
  * @param string $group_name  The name of the group to add the user to
  */
  function add_member($user_id, $group_name) {

    $this->remove_member($user_id);
    $groups = $this->get_groups_array();
    foreach ($groups as $i => $group_row) {
      if ($group_row['group_name'] == $group_name) {
        $group_id = $group_row['group_id'];
        $this->_DAO->execute("INSERT INTO " . APP__DB_TABLE_PREFIX . "user_group_member SET group_id = '$group_id', user_id = $user_id");
        break;
      }
    }

  }// /->add_member()

  /**
  * Remove members from the collection
  * Deletes the given user and role immediately from the database
  *
  * @param string $user_id  The ID of the user to remove
  *               (array) - An array of IDs to remove (all of the same user_type)
  * @param string $role   (optional) Group to remove members from. If unused, remove from all groups
  */
  function remove_member($user_id, $role = null) {
    $user_set = $this->_DAO->build_set( (array) $user_id);
    $role_clause = ($role) ? " AND user_role='$role'" : '' ;
    $this->_DAO->execute("DELETE FROM " . APP__DB_TABLE_PREFIX . "user_group_member ugm
                      WHERE ugm.user_id IN $user_set AND ugm.group_id IN
                        (SELECT group_id
                         FROM " . APP__DB_TABLE_PREFIX . "user_group
                         WHERE collection_id = '{$this->id}'
                        )");

  }// /->remove_member()

/*
* --------------------------------------------------------------------------------
* Module-Manipulation Methods
* --------------------------------------------------------------------------------
*/

/**
* Get the modules associated with this collection
*
* @return array  array ( module_id )
*/
function get_modules(){
    return $this->_DAO->fetch_assoc("SELECT DISTINCT c.module_id FROM " . APP__DB_TABLE_PREFIX . "collection c");
}

/*
* ================================================================================
* Private Methods
* ================================================================================
*/

}// /class: GroupCollection

?>
