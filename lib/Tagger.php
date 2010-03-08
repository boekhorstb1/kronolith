<?php
/**
 * Kronolith interface to the Horde_Content tagger
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @package Kronolith
 */
class Kronolith_Tagger
{
    /**
     * Local cache of the type name => ids from Content, so we don't have to
     * query for them each time.
     *
     * @var array
     */
    protected $_type_ids = array();

    /**
     * @var Content_Tagger
     */
    protected $_tagger;

    /**
     * Const'r
     *
     * @return Kronolith_Tagger
     */
    public function __construct()
    {
        // Remember the types to avoid having Content query them again.
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('calendar', 'event'));
        $this->_type_ids = array('calendar' => (int)$types[0],
                                 'event' => (int)$types[1]);

        // Cache the tagger statically
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
    }

    /**
     * Tag a kronolith object with any number of tags.
     *
     * @param string $localId       The identifier of the kronolith object.
     * @param mixed $tags           Either a single tag string or an array of tags.
     * @param string $owner         The tag owner (should normally be the owner of the resource).
     * @param string $content_type  The type of object we are tagging (event/calendar).
     *
     * @return void
     */
    public function tag($localId, $tags, $owner, $content_type = 'event')
    {
        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $this->_tagger->splitTags($tags);
        }

        $this->_tagger->tag($owner,
                   array('object' => $localId,
                         'type' => $this->_type_ids[$content_type]),
                   $tags);
    }

    /**
     * Retrieve the tags on given object(s).
     *
     * @param string $localId  The identifier of the kronolith object.
     * @param string $type     The type of object $localId represents.
     *
     * @return a tag_id => tag_name hash.
     */
    public function getTags($localId, $type = 'event')
    {
        if (!is_array($localId)) {
            $localId = array($localId);
        }
        $tags = array();
        foreach ($localId as $id) {
            $tags = $tags + $this->_tagger->getTags(array('objectId' => array('object' => $id, 'type' => $type)));
        }

        return $tags;
    }

    /**
     * Remove a tag from a kronolith object. Removes *all* tags - regardless of
     * the user that added the tag.
     *
     * @param string $localId       The kronolith object identifier.
     * @param mixed $tags           Either a tag_id, tag_name or an array of
     *                              ids or names to remove.
     * @param string $content_type  The type of object that $localId represents.
     *
     * @return void
     */
    public function untag($localId, $tags, $content_type = 'event')
    {
        $this->_tagger->removeTagFromObject(
            array('object' => $localId, 'type' => $this->_type_ids[$content_type]), $tags);
    }

    /**
     * Tag the given resource with *only* the tags provided, removing any tags
     * that are already present but not in the list.
     *
     * @param string $localId  The identifier for the kronolith object.
     * @param mixed $tags      Either a tag_id, tag_name, or array of tag_ids.
     * @param string $owner    The tag owner - should normally be the resource owner.
     * @param $content_type    The type of object that $localId represents.
     *
     * @return void
     */
    public function replaceTags($localId, $tags, $owner, $content_type = 'event')
    {
        // First get a list of existing tags.
        $existing_tags = $this->getTags($localId, $content_type);

        // If we don't have an array - split the string.
        if (!is_array($tags)) {
            $tags = $this->_tagger->splitTags($tags);
        }
        $remove = array();
        foreach ($existing_tags as $tag_id => $existing_tag) {
            $found = false;
            foreach ($tags as $tag_text) {
                if ($existing_tag == $tag_text) {
                    $found = true;
                    break;
                }
            }
            // Remove any tags that were not found in the passed in list.
            if (!$found) {
                $remove[] = $tag_id;
            }
        }

        $this->untag($localId, $remove, $content_type);
        $add = array();
        foreach ($tags as $tag_text) {
            $found = false;
            foreach ($existing_tags as $existing_tag) {
                if ($tag_text == $existing_tag) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $add[] = $tag_text;
            }
        }

        $this->tag($localId, $add, $owner, $content_type);
    }

    /**
     * Search for resources that are tagged with all of the requested tags.
     *
     * TODO: Change this to use something like a Content_Tagger::tagExists() or
     *       possibly add a $create = true parameter to ensureTags()
     *       so searching for any arbitrary text string won't cause the string
     *       to be added to the rampage_tags table as a tag (via ensureTags)
     *
     * @param array $tags  Either a tag_id, tag_name or an array.
     * @param array $filter  Array of filter parameters.
     *   (string)typeId      - only return either events or calendars, not both.
     *   (array)userId       - only include objects owned by userId(s).
     *   (array)calendarId   - restrict to events contained in these calendars.
     *
     * @return A hash of 'calendars' and 'events' that each contain an array
     *         of calendar_ids and event_uids respectively. Should this return
     *         the objects?
     */
    public function search($tags, $filter = array())
    {
        if (!empty($filter['calendarId'])) {
            // At least filter by ownerId to ease the post-filtering query.
            $owners = array();
            if (!is_array($filter['calendarId'])) {
                $filter['calendarId'] = array($filter['calendarId']);
            }
            foreach ($filter['calendarId'] as $calendar) {
                if ($GLOBALS['all_calendars'][$calendar]->get('owner')) {
                    $owners[] = $GLOBALS['all_calendars'][$calendar]->get('owner');
                }
            }
            $args = array('tagId' => $this->_tagger->ensureTags($tags),
                          'userId' => $owners,
                          'typeId' => $this->_type_ids['event']);

            // $results is an object_id => object_name hash
            $results = $this->_tagger->getObjects($args);

            //TODO: Are there any cases where we can shortcut the postFilter?
            $results = array('calendar' => array(),
                             'event' => Kronolith::getDriver()->filterEventsByCalendar($results, $filter['calendarId']));
        } else {
            $args = array('tagId' => $this->_tagger->ensureTags($tags));
            if (!empty($filter['userId'])) {
                $args['userId'] = $filter['userId'];
            }

            $cal_results = array();
            if (empty($filter['typeId']) || $filter['typeId'] == 'calendar') {
                $args['typeId'] = $this->_type_ids['calendar'];
                $cal_results = $this->_tagger->getObjects($args);
            }

            $event_results = array();
            if (empty($filter['typeId']) || $filter['typeId'] == 'event') {
                $args['typeId'] = $this->_type_ids['event'];
                $event_results = $this->_tagger->getObjects($args);
            }

            $results = array('calendar' => array_values($cal_results),
                             'event' => array_values($event_results));
        }

        return $results;
    }

    /**
     * List tags belonging to the current user beginning with $token.
     * Used for autocomplete code.
     *
     * @param string $token  The token to match the start of the tag with.
     *
     * @return A tag_id => tag_name hash
     */
    public function listTags($token)
    {
        return $this->_tagger->getTags(array('q' => $token, 'userId' => Horde_Auth::getAuth()));
    }

    /**
     * Return the data needed to build a tag cloud based on the passed in
     * user's tag data set.
     *
     * @param string $user    The user whose tags should be included.
     * @param integer $limit  The maximum number of tags to include.
     *
     * @return An array of hashes, each containing tag_id, tag_name, and count.
     */
    public function getCloud($user, $limit = 5)
    {
        return $this->_tagger->getTagCloud(array('userId' => $user,
                                                 'limit' => $limit));
    }
}
