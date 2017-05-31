<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin is used to access stock photos from unsplash
 *
 * @package    repository_stockphotos
 * @copyright  2017 Andrew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/stockphotos/vendor/autoload.php');

/**
 * Personal Youtube Plugin
 *
 * @package    repository_stockphotos
 * @copyright  2017 Andrew Davidson
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_stockphotos extends repository {

    private $applicationid = '';

    /**
     * Stock photos plugin constructor
     *
     * @param int $repositoryid
     * @param object $context
     * @param array $options
     * @param int $readonly
     * @return void
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
        global $CFG, $SITE;
        parent::__construct($repositoryid, $context, $options, $readonly = 0);

        $this->applicationid = get_config('stockphotos', 'unsplashapplicationid');

        Crew\Unsplash\HttpClient::init([
            'applicationId' => $this->applicationid,
            'utmSource' => $SITE->fullname,
        ]);
    }

    public function global_search() {
        return false;
    }

    /**
     * file types supported by unsplash plugin
     * @return array
     */
    public function supported_filetypes() {
        return array('web_image');
    }

    /**
     * Personal Youtube plugin only return external links
     * @return int
     */
    public function supported_returntypes() {
        return (FILE_INTERNAL | FILE_EXTERNAL);
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }

    /**
     * Edit/Create Admin Settings Moodle form.
     *
     * @param moodleform $mform Moodle form (passed by reference).
     * @param string $classname repository class name.
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);

        $strrequired = get_string('required');

        $mform->addElement('text', 'unsplashapplicationid', get_string('unsplash_applicationid', 'repository_stockphotos'));
        $mform->addHelpButton('unsplashapplicationid', 'unsplash_applicationid', 'repository_stockphotos');
        $mform->addRule('unsplashapplicationid', $strrequired, 'required', null, 'client');
        $mform->setType('unsplashapplicationid', PARAM_RAW_TRIMMED);
    }

    /**
     * Set options.
     *
     * @param   array   $options
     * @return  mixed
     */
    public function set_option($options = []) {
        if (!empty($options['unsplashapplicationid'])) {
            set_config('unsplashapplicationid', trim($options['unsplashapplicationid']), 'stockphotos');
            unset($options['unsplashapplicationid']);
        }

        return parent::set_option($options);
    }

    /**
     * Get plugin options
     * @param string $config
     * @return mixed
     */
    public function get_option($config = '') {
        if ($config === 'unsplashapplicationid') {
            return trim(get_config('stockphotos', 'unsplashapplicationid'));
        } else {
            $options = parent::get_option();
            $options['unsplashapplicationid'] = trim(get_config('stockphotos', 'unsplashapplicationid'));
        }

        return $options;
    }

    /**
     * Option names of plugin plugin.
     *
     * @inheritDocs
     */
    public static function get_type_option_names() {
        return [
                'unsplashapplicationid',
                'pluginname'
            ];
    }

    /**
     * Get image listing
     *
     * @param string $path
     * @param string $page no paging is used in repository_local
     * @return mixed
     */
    public function get_listing($path='', $page = 1) {
        $ret = array();
        $ret['manage'] = '';
        $ret['list']  = array();
        $ret['pages'] = 0;
        $ret['total'] = 0;
        $ret['perpage'] = 0;
        $ret['page'] = 0;

        try {
            $photos = Crew\Unsplash\Photo::all($page, 30, 'latest');
            $ret = $this->build_list($photos);
            return $ret;
        } catch (Crew\Unsplash\Exception $e) {
            throw new repository_exception('ratelimitexceeded', 'repository_stockphotos');
        }
    }

    /**
     * Return search results
     *
     * @param string $keyword The text to search
     * @param int $page
     * @return array
     */
    public function search($keyword, $page = 1) {
        $ret  = array();

        if ($keyword == '') {
            return $this->get_listing();
        }

        try {
            $photos = Crew\Unsplash\Search::photos($keyword, $page, 30);
            $ret = $this->build_search_list($photos, 30, $page);
            return $ret;
        } catch (Crew\Unsplash\Exception $e) {
            throw new repository_exception('ratelimitexceeded', 'repository_stockphotos');
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            throw new repository_exception('connectionerror', 'repository_stockphotos');
        }
    }

    /**
     *
     * @param string $photoid
     * @param string $file
     * @return string
     */
    public function get_file($photoid, $file = '') {
        $info = $this->get_photo($photoid);

        return array('path'=>$info->urls['full'], 'author'=>$info->user['name'], 'license'=>'cc');
    }

    private function get_photo($photoid) {
        $photo = Crew\Unsplash\Photo::find($photoid);
        return $photo;
    }

    private function process_photo($photo) {
        global $SITE;
        $title = get_string('photoby', 'repository_stockphotos', $photo->user['name']);
        $format = '.jpg';
        if ($photo->width > $photo->height) {
            $ratio = 100 / $photo->width;
            $height = $ratio * $photo->height;
            $width = 100;
        } else {
            $ratio = 100 / $photo->height;
            $height = 100;
            $width = $ratio * $photo->width;
        }
        $utm = "?utm_source={$SITE->fullname}&utm_medium=referral&utm_campaign=api-credit";
        return array(
            'title'=>$title.$format,
            'source'=>$photo->urls['full'].$utm,
            'id'=>$photo->id,
            'thumbnail'=>$photo->urls['thumb'],
            'thumbnail_width'=>$width,
            'thumbnail_height'=>$height,
            'date'=>$photo->created_at,
            'size'=>'unknown',
            'url'=>$photo->urls['full'],
            'author'=>$photo->user['name'],
            'license'=>'cc'
        );
    }

    /**
     * Converts result received from Crew\Unsplash\Search::photos to Filepicker/repository format
     *
     * @param mixed $photos
     * @return array
     */
    private function build_list($photos) {
        $ret = array();

        // We only want to show the first page of photos until we search.
        $ret['manage'] = '';
        $ret['list']  = array();
        $ret['pages'] = 1;
        $ret['total'] = 30;
        $ret['perpage'] = 30;
        $ret['page'] = 1;

        $iterator = $photos->getIterator();
        while($iterator->valid()){
            $photo = $iterator->current();
            $ret['list'][] = $this->process_photo($photo);
            $iterator->next();
        }
        return $ret;
    }

    /**
     * Converts result received from Crew\Unsplash\Search::photos to Filepicker/repository format
     *
     * @param mixed $photos
     * @return array
     */
    private function build_search_list($photos, $perpage, $page) {
        $ret = array();
        $ret['manage'] = '';
        $ret['list']  = array();
        $ret['pages'] = $photos->getTotalPages();
        $ret['total'] = $photos->getTotal();
        $ret['perpage'] = $perpage;
        $ret['page'] = $page;
        foreach ($photos->getResults() as $photo) {
            $photo = (object)$photo;
            $ret['list'][] = $this->process_photo($photo);
        }
        return $ret;
    }
}