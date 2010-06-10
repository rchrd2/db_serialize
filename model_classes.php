<?php
/**
 * These are the model/data classes. This is an example model to be used with
 * the example tests in db_serialize_test.php
 * 
 * @author Richard Caceres <rcaceres@ucla.edu>
 * @copyright Richard Caceres, 2010
 * @version 1.1
 * @license MIT
 */ 
///////////////////////////////////////////////////////////////////////////////
require_once(dirname(__FILE__).'/variables.php');
require_once(dirname(__FILE__).'/db_serialize.php');

///////////////////////////////////////////////////////////////////////////////
class View {
    public $uid;                // int
    public $contact_info;       // string
    public $about_us;           // string
    function __construct() {
        $this->contact_info = 'Fill Contact info in';
        $this->about_us = 'Fill About us in';
        $this->director_order = '';
    }
}
///////////////////////////////////////////////////////////////////////////////
class DirectorMan {
    public $uid;                // int
    public $director_order;     // array of ids
    function __construct() {
        $this->director_order = array();;
    }
    function createDirector() {
        connect_db();
        $dir = new Director();
        insert_object('Director', $dir);
        // Add the new director to the order array
        $this->director_order[] = $dir->uid;  
        update_object('DirectorMan', $this);
        close_db();
        return $dir;
    }
    function deleteDirector($uid) {
        connect_db();
        // del from array
        $id = array_search($uid, $this->director_order);
        if ($id !== false) {
            array_splice($this->director_order, $id, 1);
            update_object('DirectorMan', $this); 
        }
        $dir = get_object('Director', $uid);
        //var_dump($dir);
        if ($dir != false) $dir->cleanDelete();
    }
    function getDirectors() {
        connect_db();
        $directors = array();
        foreach($this->director_order as $uid) {
            $directors[] = get_object('Director', $uid);
        }
        close_db();
        return $directors;
    }
}
///////////////////////////////////////////////////////////////////////////////
class Director {
    public $uid;                // int
    public $name;               // string
    public $projects;           // array of ids
    public $is_public;          // bool
    
    /* constructor */
    function __construct() {
        $this->name = 'no name: ' . date('r');
        $this->projects = array();
        $this->is_public = false;
    }
    
    /* use for deleting */
    function cleanDelete() {
        //echo 'Director->cleanDelete()';
              
        // delete all associated projects
        foreach($this->projects as $pid) {
            connect_db();          
            //var_dump($pid);
            $proj = get_object('Project', $pid);
            //var_dump($proj);
            if($proj !== false) $proj->cleanDelete();
        }
        // delete this
        delete_object('Director', (int)$this->uid);
    }
    
    function getProjects() {
        connect_db();
        $objs = array();
        /**
         * @TODO optimize this query
         */
        foreach($this->projects as $uid) {
            $objs[] = get_object('Project', $uid);
        }
        //close_db();
        return $objs;        
    }
    
    function createProject($type=0) {
        connect_db();
        // create and add the project to the db
        $obj = new Project($type);
        insert_object('Project', $obj);
        // Add the project to the array
        $this->projects[] = $obj->uid;  
        update_object('Director', $this);
        //close_db();
        return $obj;     
    }
    function deleteProject($pid) {
        connect_db();        
        // delete the project from this array
        $id = array_search($pid, $this->projects);
        if ($id !== false) {
            array_splice($this->projects, $id, 1);
            update_object('Director', $this); 
        }
        $proj = get_object('Project', $pid);
        if ($proj !== false) $proj->cleanDelete();
        //close_db();        
    }    
}
///////////////////////////////////////////////////////////////////////////////
class Project {
    public $uid;                 // int
    public $type;                // int: 0=video, 1=image
    public $file;                // string -- the path to the file
    public $thumb;               // string -- the path to the thumb
    public $categories;          // array[string] -- an array of strings
    public $client;              // string
    public $title;               // string
    public $desc;                // string
    public $is_public;           // bool
    
    /* constructor */
    function __construct($type=0) {
        $this->type = $type;
        $this->file = '';
        $this->thumb = '';
        $this->categories = array();
        $this->client = '';
        $this->title = 'untitled';
        $this->desc = '';
        $this->is_public = false;
    }
    
    function cleanDelete() {
        // delete this from db
        connect_db();
        delete_object('Project', (int)$this->uid);
        // delete the files
        safeDelete(UPLOAD_PATH . '/' . $this->file);
        safeDelete(UPLOAD_PATH . '/' . $this->thumb);
    }
}
///////////////////////////////////////////////////////////////////////////////