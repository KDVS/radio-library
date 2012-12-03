<?php
/*******************************

NEEDS SECURITY! controller/action based

*******************************/


//controller class that handles most general ajax requests
//like autocompletion in forms
class AjaxController extends MyController {
    function init() {
        //call default init from MyController
        //parent::init();
		$this->view->addHelperPath('application/views/helpers', 'My_View_Helper');
		
    }
    function preDispatch() {
		Zend_Loader::loadClass('Zend_Json');
	}
    function indexAction(){
        $this->view->title = "Admin";
        $this->render();
    }

	/*auto complete functions */
	
	function autoCompleteUserNamesAction(){
		//if ajax call has sent correct param
		if($search_term = $this->getRequest()->getParam('search_term')){
			/**/
			//load model
	        Zend_Loader::loadClass('Admin'); //admin has Users which we need
		
			$admin = new Admin();
			$user_list = $admin->findUsersByName($search_term); //returns something safe
			$this->view->users = $user_list;
			$this->render('autocomplete-usernames');
		}
	}
	function autoCompleteLabelsAction(){
		//if ajax call has sent correct param
		if($search_term = $this->getRequest()->getParam('search_term')){
			/**/
			//load model
	        Zend_Loader::loadClass('Music'); //admin has Users which we need
		
			$music = new Music();
			$label_list = $music->findLabels($search_term); //returns something safe
			$this->view->labels = $label_list;
			$this->render('autocomplete-labels');
		}
	}
	function autoCompleteAlbumsAction(){
		//if ajax call has sent correct param
		if($search_term = $this->getRequest()->getParam('search_term')){
			//load model
	        Zend_Loader::loadClass('Music'); //admin has Users which we need
		
			$music = new Music();
			$album_list = $music->findAlbums($search_term); //returns something safe
			$this->view->albums = $album_list;
			$this->render('autocomplete-albums');
		}
	}
	function autoCompleteShowNamesAction(){
		//if ajax call has sent correct param
		if($search_term = $this->getRequest()->getParam('search_term')){
			if($season_id = $this->getRequest()->getParam('season_id')){
				//load model
		        Zend_Loader::loadClass('Schedule'); //admin has Users which we need
		
				$model = new Schedule();
				$show_list = $model->findShowsBySeason($search_term, $season_id); //returns something safe
				$this->view->shows = $show_list;
				$this->render('autocomplete-shows');
			}
		}
	}
	function userEditFormAction(){
		//load model
	    Zend_Loader::loadClass('Admin'); //admin has Users which we need
		
		require_once('edituser.form.php'); //quickform class
        $form = new edituserForm('/ajax/userEditForm', 'Edit User', true); //pass the action param of form as string, button val, and true for ajax
		//remove username (cannot be edited)
		$form->removeElement('username');
			
		//remove password elements so we do not edit those
		$form->removeElement('pass_head');
		$form->removeElement('password1');
		$form->removeElement('password2');
		
		//give surrounding form div an id (for error messages and...)
		$this->view->form_id = 'wrap_edituserform';
		
		//change renderer for xhtml
		$renderer = new HTML_QuickForm_Renderer_Tableless();
		
		
		//if the user_id param is there, then this is just a form request
        if($this->getRequest()->getParam('user_id')){
			require_once('edituser.form.php'); //quickform class
			

			
			//lets get the user
			$user_id = $this->getRequest()->getParam('user_id');
			

			$class = new Admin();
			
			$user = $class->getUser($user_id)->toArray();
			
			//format db results for use in form
			
			
			//address
			$user['address_group'] = array(
			    'address_city' => $user['address_city'],
			    'address_state' => $user['address_state'],
			    'address_zip' => $user['address_zip']
			    );
			
			//phones
			$pri_phone = explode("-", $user['primary_phone']);
			$sec_phone = explode("-", $user['secondary_phone']);

			$user['primary_phone'] = array(
			    "pri_area" => $pri_phone[0],
			    "pri_no1" => $pri_phone[1],
			    "pri_no2" => $pri_phone[2]
			    );
			    
			if(count($sec_phone) == 3){
			    $user['secondary_phone'] = array(
    			    "sec_area" => $sec_phone[0],
    			    "sec_no1" => $sec_phone[1],
    			    "sec_no2" => $sec_phone[2]
    			    );
		    } else {
		        unset($user['secondary_phone']);
	        }
			
			$form->setDefaults($user);
			

			
			//$this->view->html = $form->toHtml();
			$form->accept($renderer);
			$this->view->html = $renderer->toHtml();
			$this->render('purehtml');
			return;
		}
		//we must have posted to it!
        if ($form->validate()) {
            //get data from form
            $data = $form->exportValues();
            //remove submit element (so it wont clog up db update/insert)
            unset($data['submit']);
            
            //deal with address group
            $data = array_merge($data, $data['address_group']);
            unset($data['address_group']);
            
            //deal with telephone group
            $pri_phone = $data['primary_phone'];
            $data['primary_phone'] = $pri_phone['pri_area'] . '-' . $pri_phone['pri_no1'] . '-' . $pri_phone['pri_no2'];
            
            $sec_phone = $data['secondary_phone'];
            if($sec_phone['sec_area'] == '' || $sec_phone['sec_area'] == '' || $sec_phone['sec_area'] == ''){
                unset($data['secondary_phone']);
            } else {
                $data['secondary_phone'] = $sec_phone['sec_area'] . '-' . $sec_phone['sec_no1'] . '-' . $sec_phone['sec_no2'];
            }
            
            //get model
            $admin = new Admin();
            
            //insert user            
            $user_id = $admin->updateUser($data);        
            
			$this->view->the_message = 'User edited successfully. Begin typing above to edit another user.';
        } else {
			//didn't validate, so indicate error
			$this->view->error = true;
            //$this->view->the_form = $form->toHtml();
			$form->accept($renderer);
			$this->view->the_form = $renderer->toHtml();
        }
        
        $this->render('form');
    }

	/*music stuff */
	
	function labelAddFormAction(){
		//load model
	    Zend_Loader::loadClass('Music'); //admin has Users which we need
		
		require_once('editlabel.form.php'); //quickform class
        $form = new editLabelForm('/music/labelAddForm', 'Add Label', true); //pass the action param of form as string, button val, and true for ajax
		//give surrounding form div an id (for error messages and...)
		$this->view->form_id = 'wrap_addlabelform';
		
		//change renderer for xhtml
		$renderer = new HTML_QuickForm_Renderer_Tableless();
		//change template for form element explanations
		$formTemplateForAjax = "\n\t<div style=\"display: none;\">\n{hidden}\t</div>\n{content}\n";
        $renderer->setFormTemplate($formTemplateForAjax);

        if ($form->validate()) {
            //get data from form
            $data = $form->exportValues();
            //remove submit element (so it wont clog up db update/insert)
            unset($data['submit']);
            
            //get model
            $music = new Music();
            
            //insert user            
            $user_id = $music->updateLabel($data);        
            
			$this->view->the_message = 'Label Added Successfully. Now use Magic Label Selector to select the new one added.';
        } else {
			//didn't validate, so indicate error (also is there on first showing, so don't eval scripts to prevent shaking)
			$the_message = 'Be sure to click Add Label (and successfully add the label) before continuing to add the album.';
			$the_message .= " <a href=\"javascript:void(0)\" onclick=\" $('addLabelForm').update('').removeClassName('ajax_form');\">Click to Cancel</a>";
			$this->view->the_message = $the_message;
			$this->view->error = true;
			$form->accept($renderer);
			$this->view->the_form = $renderer->toHtml();
        }
        $this->render('form');
	}
	function albumEditFormAction(){
		//load model
	    Zend_Loader::loadClass('Music'); //admin has Users which we need
		
		require_once('editalbum.form.php'); //quickform class
        $form = new editAlbumForm('/ajax/albumEditForm', 'Edit Album', true); //pass the action param of form as string, button val, and true for ajax
		
		//give surrounding form div an id (for error messages and...)
		$this->view->form_id = 'wrap_editalbumform';
		
		//change renderer for xhtml
		$renderer = new HTML_QuickForm_Renderer_Tableless();		
		
		
		//remove rules/elements that are not used for editing
		
		
		//this removes the Add Label link
		$form->getElement('label_selected')->setLabel('Label Selected');
		
		
		
		//if the album_id param is there, then this is just a form request
        if($this->getRequest()->getParam('album_id')){
			//lets get the album
			$album_id = $this->getRequest()->getParam('album_id');
			$class = new Music();
			$album = $class->getAlbum($album_id)->toArray();
			
			$label = $class->getLabel($album['label_id'])->toArray();
			//take that info and format so the form can use it
			//fix release date
			$date_defaults = array(
		        //'d' => $album['release_day'],        
		        //'m' => $album['release_month'],
		        'Y' => $album['release_year']
		    );
			$album['release_date'] = $date_defaults;
			
			//fix tracking info
			
			$album['track_con'] = array('track_condition' => $album['track_con']);
			
			//add label info to form
			$album['label_selected'] = $label['label_name'];
			
			//set the values
			$form->setDefaults($album);			

			$form->accept($renderer);
			$this->view->html = $renderer->toHtml();
			$this->render('purehtml');
			return;
		}
		//we must have posted to it!
        if ($form->validate()) {
            //get data from form
            $data = $form->exportValues();
            //remove submit element (so it wont clog up db update/insert)
            unset($data['submit']);
            
			unset($data['label_selected']);
			unset($data['label_autocomplete']);
			
			$data['release_year'] = $data['release_date']['Y'];
			unset($data['release_date']);
			
			//tracking stuff
			$num_track_months = '3'; //number of months to track
			//only do if track_con is set (should be), contains the radio element, and that element is true
			if(isset($data['track_con']) && isset($data['track_con']['track_condition']) && $data['track_con']['track_condition']){
				$data['trackend_date'] = date('Y-m-d', strtotime($data['add_datetime'] . '+'. $num_track_months .' months'));
				$data['track_con'] = true; //to remove the array that is the default
			} else {
				$data['track_con'] = false;
			}

            //get model
            $admin = new Music();
            
            //insert user            
            $user_id = $admin->updateAlbum($data);        
            
			$this->view->the_message = 'Album edited successfully. Begin typing above to edit another album.';
        } else {
			//didn't validate, so indicate error
			$this->view->error = true;
			$form->accept($renderer);
			$this->view->the_form = $renderer->toHtml();
        }
        
        $this->render('form');
    }

	function deleteAlbumAction(){
		//load model
	    Zend_Loader::loadClass('Music'); //admin has Users which we need

        if($album_id = $this->getRequest()->getParam('album_id')){	
			$music = new Music();
			$music->deleteAlbum($album_id);
			$this->view->html = 'OK';
			
		} else {
			$this->view->html = 'ERROR';
		}
		
		$this->render('purehtml');
	}

	function labelEditFormAction(){
		//load model
	    Zend_Loader::loadClass('Music'); //admin has Users which we need
		
		require_once('editlabel.form.php'); //quickform class
        $form = new editLabelForm('/music/labelEditForm', 'Edit Label', true); //pass the action param of form as string, button val, and true for ajax
		//give surrounding form div an id (for error messages and...)
		$this->view->form_id = 'wrap_editlabelform';
		
		//change renderer for xhtml
		$renderer = new HTML_QuickForm_Renderer_Tableless();
		
		//$formTemplateForAjax = "\n\t<div style=\"display: none;\">\n{hidden}\t</div>\n{content}\n";
        //$renderer->setFormTemplate($formTemplateForAjax);
		//if the user_id param is there, then this is just a form request
        if($this->getRequest()->getParam('label_id')){
			require_once('edituser.form.php'); //quickform class
			//lets get the user
			$label_id = $this->getRequest()->getParam('label_id');
			

			$class = new Music();
			
			$label = $class->getLabel($label_id)->toArray();
			$form->setDefaults($label);
			

			
			//$this->view->html = $form->toHtml();
			$form->accept($renderer);
			$this->view->html = $renderer->toHtml();
			$this->render('purehtml');
			return;
		}
        if ($form->validate()) {
            //get data from form
            $data = $form->exportValues();
            //remove submit element (so it wont clog up db update/insert)
            unset($data['submit']);
            
            //get model
            $music = new Music();
            
            //insert user            
            $user_id = $music->updateLabel($data);        
            
			$this->view->the_message = 'Label Edited Successfully. Begin typing above to edit another';
        } else {
			$this->view->error = true;
			$form->accept($renderer);
			$this->view->the_form = $renderer->toHtml();
        }
        $this->render('form');
	}
	/*schedule stuff */
	function showEditFormAction(){
		//load model
	    Zend_Loader::loadClass('Schedule'); //admin has Users which we need
		
		require_once('editshow.form.php'); //quickform class
        $form = new editShowForm('/ajax/showEditForm', 'Edit Show', true); //pass the action param of form as string, button val, and true for ajax
		
		//give surrounding form div an id (for error messages and...)
		$this->view->form_id = 'wrap_editshowform';
		
		//change renderer for xhtml
		$renderer = new HTML_QuickForm_Renderer_Tableless();
		
		
		//if the user_id param is there, then this is just a form request
        if($this->getRequest()->getParam('show_id')){
			require_once('editshow.form.php'); //quickform class
			//lets get the user
			$show_id = $this->getRequest()->getParam('show_id');
			
			$class = new Schedule();
			
			$show = $class->getShow($show_id)->toArray();
			
			//put the genres in their groups
			$show['genres_group1']['genre_metal'] = $show['genre_metal'];
			$show['genres_group1']['genre_international'] = $show['genre_international'];
			$show['genres_group1']['genre_reggae'] = $show['genre_reggae'];
			
			$show['genres_group2']['genre_classical'] = $show['genre_classical'];
			$show['genres_group2']['genre_eclectic'] = $show['genre_eclectic'];
			$show['genres_group2']['genre_electronic'] = $show['genre_electronic'];	
			
			$show['genres_group3']['genre_hardcore'] = $show['genre_hardcore'];
			$show['genres_group3']['genre_jazz'] = $show['genre_jazz'];
			$show['genres_group3']['genre_folk'] = $show['genre_folk'];
			$show['genres_group3']['genre_rock'] = $show['genre_rock'];

			$show['genres_group4']['genre_indie'] = $show['genre_indie'];
			$show['genres_group4']['genre_blues'] = $show['genre_blues'];
			$show['genres_group4']['genre_industrial'] = $show['genre_industrial'];
			$show['genres_group4']['genre_punk'] = $show['genre_punk'];				

			$show['genres_group5']['genre_hiphop'] = $show['genre_hiphop'];
			$show['genres_group5']['genre_latin'] = $show['genre_latin'];
			$show['genres_group5']['genre_noise'] = $show['genre_noise'];
			$show['genres_group5']['genre_experimental'] = $show['genre_experimental'];
			
			$form->setDefaults($show);		

			$form->accept($renderer);
			$this->view->html = $renderer->toHtml();
			$this->render('purehtml');
			return;
		}
		//we must have posted to it!
        if ($form->validate()) {
            //get data from form
            $data = $form->exportValues();
            //remove submit element (so it wont clog up db update/insert)
            unset($data['submit']);
            
            //get model
            $admin = new Schedule();
            
            //deal with genre groups
            $num_genre_groups = 5;
            for($i = 1; $i <= $num_genre_groups; $i++){
                if(isset($data['genres_group'.$i])){
                    foreach($data['genres_group'.$i] as $key => $val){
                        $data[$key] = $val;
                    }
                    unset($data['genres_group'.$i]);
                }
            }
            
            //update  show            
            $user_id = $admin->updateShow($data);        
            
			$this->view->the_message = 'Show edited successfully. Begin typing above to edit another show from the same season.';
        } else {
			//didn't validate, so indicate error
			$this->view->error = true;
            //$this->view->the_form = $form->toHtml();
			$form->accept($renderer);
			$this->view->the_form = $renderer->toHtml();
        }
        
        $this->render('form');
    }

	function weekScheduleGetItemsAction(){
		//load model
		
	    Zend_Loader::loadClass('Schedule');
		$model = new Schedule();
		if($season_id = $this->getRequest()->getParam('season_id')){
			$event_items = $model->getAllJoinedEventsBySeason($season_id);
			$this->view->the_events = $event_items;
			//var_dump($event_items);
		}
		$this->render('week-schedule-getitems');
	}
	function weekScheduleSaveAction(){
		//load model
		
	    Zend_Loader::loadClass('Schedule');
		$model = new Schedule();
		
		if ($this->getRequest()->getParam('saveAnItem')){
			
			if ($this->getRequest()->getParam('id') && !strstr($this->getRequest()->getParam('id'), 'new')){
				$event_id = $this->getRequest()->getParam('id');
				$data['id'] = $event_id;
			}
			$data['season_id'] = $this->getRequest()->getParam('season_id');
						
			$round_numerator = 60 * 30;
			//$round_numerator = 60 * 15 // 60 seconds per minute * 15 minutes equals 900 seconds
			//$round_numerator = 60 * 60 or to the nearest hour
			//$round_numerator = 60 * 60 * 24 or to the nearest day

			// Calculate time to nearest 30 minutes!
			$fix_time = '';
			$end_min = (int)date('i', strtotime($this->getRequest()->getParam('eventEndDate')));
			if ($end_min == 15 || $end_min == 45){ //this is because js does cal differently
				$fix_time = ' -15 minute';
			}
			$rounded_time = ( round ( strtotime($this->getRequest()->getParam('eventEndDate') . $fix_time) / $round_numerator ) * $round_numerator );
			
			$data['start_time'] = date("H:i:s",strtotime($this->getRequest()->getParam('eventStartDate')));
			$data['end_time'] = date("H:i:s", $rounded_time);
			
			//end of hour
			if($data['end_time'] == '00:00:00'){
				$data['end_time'] = '24:00:00';
			}
			
			$dotw = date("w",strtotime($this->getRequest()->getParam('eventStartDate')));
			$dotw = $dotw - 1;
			if($dotw < 0){
				$dotw = 6;
			}
			$data['dotw'] = $dotw;
			
			//insert event            
            $event_id = $model->updateEvent($data);
			$this->view->html =  "$event_id";

		}
		
		$this->render('purehtml');
	}
	function weekScheduleDeleteAction(){
		if ($event_id = $this->getRequest()->getParam('eventToDeleteId')){
			Zend_Loader::loadClass('Schedule');
			$model = new Schedule();
			$model->deleteEvent($event_id);
			$this->view->html =  'OK';
			$this->render('purehtml');
		}
	}
	function weekScheduleGetEventDescriptionAction(){
		//load model
		
	    Zend_Loader::loadClass('Schedule');
		$model = new Schedule();
		if($event_id = $this->getRequest()->getParam('event_id')){
			$event = $model->getJoinedEvent($event_id);
			//$this->view->html = $this->view->CreateEventDescription($event);
			$this->view->html = '69';
		}
		$this->render('purehtml');
	}
	
	/* playlist ajax */
	function updateAllTracksAction(){
		$decoded_array = Array();
		
		if($json = $this->getRequest()->getParam('json')){
			$decoded = Zend_Json::decode($json);
			
			$position = 1;
			foreach($decoded as $trk){
				if($trk['album_id'] != '' || $trk['track_num'] != '' || $trk['artist_name'] != '' || $trk['song_name'] != '' || $trk['album_name'] != '' || $trk['label_name'] != ''){ 
					$trk['position'] = $position;
					$position++;
					$decoded_array[] = $this->updateTrack($trk);
				}
			}
			
			$this->view->html = Zend_Json::encode($decoded_array);
			
			$this->render('purehtml');
		}
	}
	function updateTrackAction(){

		if($json = $this->getRequest()->getParam('json')){
			$decoded = Zend_Json::decode($json);
			$decoded = $this->updateTrack($decoded);
			$this->view->html = Zend_Json::encode($decoded);
			$this->render('purehtml');
		}
	}
	function updateTrack($decoded){ //method called by saveAll and individual track saving
			Zend_Loader::loadClass('Playlists');
			Zend_Loader::loadClass('Music');
			$play = new Playlists();
			$music = new Music();
			$disable = false;
			$disable_track_num = false;
			
			$temp_track_id = $decoded['track_id']; //the item id
			
			//prevent clog
			unset($decoded['track_id']);
			unset($decoded['submit']);
			
			if(isset($decoded['from_home'])){
				$decoded['from_home'] = true;
			} else {
				$decoded['from_home'] = false;
			}
			if(isset($decoded['request'])){
				$decoded['request'] = true;
			} else {
				$decoded['request'] = false;
			}
			if(isset($decoded['airbreak_after'])){
				$decoded['airbreak_after'] = true;
			} else {
				$decoded['airbreak_after'] = false;
			}
			
			//check if album id and track number are given, if so - grab some stuff
			if(isset($decoded['album_id']) && $decoded['album_id'] != ''){
				$album = $music->getAlbumJoined($decoded['album_id']);

				if($album){
					
					//var_dump($album['songs']);
					//add album info
					$decoded['album_name'] = $album['title'];
					$decoded['label_name'] = $album['label_name'];
					$decoded['artist_name'] = $album['artist'];
					if(isset($album['artist_display']) && $album['artist_display'] != ''){
						$decoded['artist_name'] = $album['artist_display'];
					}
					
					//now to determine if this song should be tracked for spin calcs
					if (isset($album['track_con']) && $album['track_con'] && strtotime($album['trackend_date']) > time()){
						$decoded['current'] = true;
					}
					
					//send disable flag so user will not edit fields grabbed from db
					$disable = true;
					
					//was a track_num entered?
					
					if(isset($decoded['track_num']) && $decoded['track_num'] != ''){
						//do we have a song saved with that track_num? if we do, and the user isn't trying to update an incorrect enntry, give them back what we got
						$decoded['track_num'] = (int)$decoded['track_num'];
						if(isset($album['songs'][$decoded['track_num']]) && (!isset($decoded['id']) || $decoded['id'] == '' || $decoded['song_name'] == '')){ //first time save
							$decoded['song_name'] = $album['songs'][$decoded['track_num']]['title'];

						} else if (isset($decoded['song_name']) && $decoded['song_name'] != ''){ //no song found and name supplied, so save it!
							if(isset($album['songs'][$decoded['track_num']])){ //are we updating the song with new info from user?
								$temp_song['id'] = $album['songs'][$decoded['track_num']]['id'];
							}
							
							$temp_song['album_id'] = $album['id'];
							$temp_song['title'] = $decoded['song_name'];
							$temp_song['track_num'] = $decoded['track_num'];
							$song_id = $music->updateSong($temp_song);
						}
						//don't allow user edit track num
						$disable_track_num = true;
					}
					
				}
				
			}
			
			
			$track_id = $play->updateTrack($decoded);
			
			//give back info
			$decoded['id'] = $track_id;
			$decoded['track_id'] = $temp_track_id;
			
			$decoded['disable'] = $disable;
			$decoded['disable_track_num'] = $disable_track_num;

			return $decoded;

		
	}
	function deleteTrackAction(){
		Zend_Loader::loadClass('Playlists');
		
		if($json = $this->getRequest()->getParam('json')){
			$this->view->html = 'OK';
			$decoded = Zend_Json::decode($json);
			$play = new Playlists();
			if(isset($decoded['id']) && $decoded['id']){
				$the_id = $play->deleteTrack($decoded['id']);
			}

		}
		$this->render('purehtml');
	}
	
	/* //saveall is called instread of this function*/
	function updateTrackOrderAction(){

		Zend_Loader::loadClass('Playlists');

		$play = new Playlists();
		$this->view->html = 'OK';
		
		if($json = $this->getRequest()->getParam('json')){
			$decoded = Zend_Json::decode($json);
			
			$position = 1;
			foreach($decoded as $trk){
				if($trk['id'] != ''){
					$temp_track['id'] = $trk['id'];
					$temp_track['playlist_id'] = $trk['playlist_id'];
					$temp_track['position'] = $position;
					$track_id = $play->updateTrack($temp_track);
					if(!$track_id){
						$this->view->html = 'ERROR';
					}			
					$position++;
				}
			}							
			
		} else {
			$this->view->html = 'ERROR';
		}
		$this->render('purehtml');
	}
	
	function streamingScheduleAction(){
		Zend_Loader::loadClass('Schedule');
		$model = new Schedule();
		
		//if($season_id = $model->getCurrentSeasonID()){
		if($season_id = 25){
			$event_items = $model->getAllJoinedEventsBySeason($season_id);
			//var_dump($event_items);
			
			$streams = $model->getAllOldStreams();
			$lookup = array();
			foreach($streams as $stream){
				$lookup[$stream['dow']][$stream['start_time']] = array('dj_name' => $stream['dj_name'], 'show_name' => $stream['show_name']);
			}
			$the_html='';
			foreach($event_items as $event){
				if(!isset($event['dj1_name']) ||  $event['dj1_name'] == ''){
					$the_html .= 'DJ Name{';
				} else {
					$the_html .= $event['dj1_name'] . '{';
				}
				//$the_html .= $event['show_name'] . '{';
				if(!isset($event['show_name']) ||  $event['show_name'] == ''){
					$the_html .= 'Show Name{';
				} else {
					$the_html .= $event['show_name'] . '{';
				}
				
				$the_html .= '2003-01-01 ' .$event['start_time'] . '.0{';
				
				if($event['end_time'] == '24:00:00'){
					$the_html .= '2003-01-01 00:00:00.0{';
				} else {
					$the_html .= '2003-01-01 ' .$event['end_time'] . '.0{';
				}
				$the_html .= $event['dotw'] . '{';
				//$the_html .= chop(str_replace("\n", ' ', $event['description'])) . '{';
				if(!isset($event['description']) ||  $event['description'] == ''){
					$the_html .= 'Show Description{';
				} else {
					$the_html .= chop(str_replace("\n", ' ', $event['description'])) . '{';
				}
				$the_html .= $event['show_id'] . '{';
				//now add old showname and dj_name
				if(isset($lookup[$event['dotw']]['2003-01-01 ' . $event['start_time']])){
					$old_show = $lookup[$event['dotw']]['2003-01-01 ' . $event['start_time']];
				} else {
					$old_show = array('dj_name' => 'Old DJ Name', 'show_name' => 'Old Show Name');
				}
				$the_html .= $old_show['dj_name'] . '{' . $old_show['show_name'];
				$the_html .= "\n";
				if(isset($event['alt_show_id'])){
					if(!isset($event['alt_dj1_name']) ||  $event['alt_dj1_name'] == ''){
						$the_html .= 'DJ Name{';
					} else {
						$the_html .= $event['alt_dj1_name'] . '{';
					}
					//$the_html .= $event['alt_show_name'] . '{';
					if(!isset($event['alt_show_name']) ||  $event['alt_show_name'] == ''){
						$the_html .= 'Alt DJ Name{';
					} else {
						$the_html .= $event['alt_show_name'] . '{';
					}
					$the_html .= '2003-01-01 ' .$event['start_time'] . '.0{';
					if($event['end_time'] == '24:00:00'){
						$the_html .= '2003-01-01 00:00:00.0{';
					} else {
						$the_html .= '2003-01-01 ' .$event['end_time'] . '.0{';
					}
					$the_html .= $event['dotw'] . '{';
					//$the_html .= chop(str_replace("\n", ' ', $event['alt_description'])) . '{';
					if(!isset($event['alt_description']) ||  $event['alt_description'] == ''){
						$the_html .= 'Alt Description{';
					} else {
						$the_html .= chop(str_replace("\n", ' ', $event['alt_description'])) . '{';
					}
					$the_html .= $event['alt_show_id'] . '{';
					//now add old showname and dj_name
					$old_show = $lookup[$event['dotw']]['2003-01-01 ' . $event['start_time']];

					$the_html .= $old_show['dj_name'] . '{' . $old_show['show_name'];
					$the_html .= "\n";
				}
				

			}
			$this->view->html = $the_html;
		}
		$this->render('purehtml');
	}
	function streamingScheduleJSONAction(){
		Zend_Loader::loadClass('Schedule');
		$model = new Schedule();
		
		if($season_id = $model->getCurrentSeasonID()){
		//if($season_id = 26){
			$event_items = $model->getAllJoinedEventsBySeason($season_id);
			//var_dump($event_items);

            $json_array = array();
			foreach($event_items as $event){
			    $temp_array = array();
				if(!isset($event['dj1_name']) ||  $event['dj1_name'] == ''){
					$temp_array['dj_names'] = 'DJ Name';
				} else {
					$temp_array['dj_names'] = $event['dj1_name'];
				}
                if(isset($event['dj2_name']) &&  $event['dj2_name'] != ''){
					$temp_array['dj_names'] .= ' & ' . $event['dj2_name'];
				} 
                if(isset($event['dj3_name']) &&  $event['dj3_name'] != ''){
					$temp_array['dj_names'] .= ' & ' . $event['dj3_name'];
				}

				if(!isset($event['show_name']) ||  $event['show_name'] == ''){
					$temp_array['show_name'] = 'Show Name';
				} else {
					$temp_array['show_name'] = $event['show_name'];
				}
				
				$temp_array['start_hour'] = date('H', strtotime($event['start_time']));
				$temp_array['start_min'] = date('i', strtotime($event['start_time']));
				$temp_array['end_min'] = date('i', strtotime($event['end_time']));
				
				if($event['end_time'] == '24:00:00'){
					$temp_array['end_hour'] = 24;
				} else {
					$temp_array['end_hour'] = date('H', strtotime($event['end_time']));
				}

				
				$temp_array['dotw'] = $event['dotw'];
				
				if(!isset($event['description']) ||  $event['description'] == ''){
					$temp_array['description'] = 'Show Description';
				} else {
					$temp_array['description'] = chop(str_replace("\n", ' ', $event['description']));
				}
				
				$temp_array['show_id'] = $event['show_id'];
                
                $json_array[$event['id']] = $temp_array;

                //add alternate show if it exists
				if(isset($event['alt_show_id'])){
                    $temp_array = array();
    				if(!isset($event['alt_dj1_name']) ||  $event['alt_dj1_name'] == ''){
    					$temp_array['dj_names'] = 'DJ Name';
    				} else {
    					$temp_array['dj_names'] = $event['alt_dj1_name'];
    				}
                    if(isset($event['alt_dj2_name']) &&  $event['alt_dj2_name'] != ''){
    					$temp_array['dj_names'] .= ' & ' . $event['alt_dj2_name'];
    				} 
                    if(isset($event['alt_dj3_name']) &&  $event['alt_dj3_name'] != ''){
    					$temp_array['dj_names'] .= ' & ' . $event['alt_dj3_name'];
    				}

    				if(!isset($event['alt_show_name']) ||  $event['alt_show_name'] == ''){
    					$temp_array['show_name'] = 'Show Name';
    				} else {
    					$temp_array['show_name'] = $event['alt_show_name'];
    				}

    				$temp_array['start_hour'] = date('H', strtotime($event['start_time']));
    				$temp_array['start_min'] = date('i', strtotime($event['start_time']));
    				$temp_array['end_min'] = date('i', strtotime($event['end_time']));

    				if($event['end_time'] == '24:00:00'){
    					$temp_array['end_hour'] = 24;
    				} else {
    					$temp_array['end_hour'] = date('H', strtotime($event['end_time']));
    				}


    				$temp_array['dotw'] = $event['dotw'];

    				if(!isset($event['alt_description']) ||  $event['alt_description'] == ''){
    					$temp_array['description'] = 'Show Description';
    				} else {
    					$temp_array['description'] = chop(str_replace("\n", ' ', $event['alt_description']));
    				}

    				$temp_array['show_id'] = $event['alt_show_id'];

                    $json_array[$event['id'].'_alt'] = $temp_array;

				} 
				
			}
			$this->view->html = Zend_Json::encode($json_array);
		}
		$this->render('purehtml');
	}
	function imageSearchAction(){
	    include('html_dom_parser.php');
	    
	    if($search_type = $this->getRequest()->getParam('type')){
	        $item_id = $this->getRequest()->getParam('item_id');
	        $page = $this->getRequest()->getParam('page');
	        
	        $nexturl = '/ajax/imageSearch/item_id/'.$item_id.'/page/' . (string)($page + 1);
            $prevurl = '/ajax/imageSearch/item_id/'.$item_id.'/page/' . (string)($page - 1);

	        
	        $the_html = '<div id="modal_box">';
	        $the_html .= '<div id="image_search_results" style="text-align: center;">';
	        $images_found = false;
	        switch($search_type){
	            case 'music':
	                $artist = $this->getRequest()->getParam('artist');
                    $title = $this->getRequest()->getParam('title');
                    //$dom = file_get_dom('http://albumart.org/'); //there just to seem legit (shady)
                    $dom = file_get_dom('http://albumart.org/covers2.php?pg='. $page .'&type=music&search=' . urlencode("$artist $title"));

                    // find all image
                    $result = $dom->find('img');
                    foreach($result as $v) {
                        $modalbox_url = '/ajax/imageConfirm/item_id/'.$item_id;
                        //$modalbox_cmd = 'Modalbox.show(\''.$modalbox_url.'\', {title: \'Image Confirm\', params: {url:\'' .$v->src . '\'}}); return false;';
                        $modalbox_cmd = 'showImageConfirm(\'' . $v->src . '\', ' . $item_id . ', \'' . $search_type .'\', ' . $page .'); return false;';
                        $the_html .= '<a href="#" onclick="'.$modalbox_cmd.'" />'; 
                        $the_html .=  '<img src="' . $v->src . '" height="140" /> ';
                        $the_html .= '</a>';
                        $images_found = true;
                    }
                    //url for next button
                    $nexturl .= '/type/music/artist/' . $artist . '/title/' . $title;
                    $prevurl .= '/type/music/artist/' . $artist . '/title/' . $title;
	                break;
	            case 'book':
                    $author = $this->getRequest()->getParam('author');
                    $title = $this->getRequest()->getParam('title');

                    $dom = file_get_dom('http://www.slothradio.com/covers/books.php?page='.$page.'&adv=&title='.urlencode($title).'&author='.urlencode($author).'&keywords=' );

                    // find all image
                    $result = $dom->find('img');
                    foreach($result as $v) {
                        if(strstr($v->src, 'images-amazon')){
                            $modalbox_url = '/ajax/imageConfirm/item_id/'.$item_id;
                            //$modalbox_cmd = 'Modalbox.show(\''.$modalbox_url.'\', {title: \'Image Confirm\', params: {url:\'' .$v->src . '\'}}); return false;';
                            $modalbox_cmd = 'showImageConfirm(\'' . $v->src . '\', ' . $item_id . ', \'' . $search_type .'\', ' . $page .'); return false;';
                            $the_html .= '<a href="' . $v->src . '" onclick="'.$modalbox_cmd.'" />'; 
                            $the_html .=  '<img src="' . $v->src . '" height="180" /> ';
                            $the_html .= '</a>';
                            $images_found = true;
                        }
                    }
                    //url for next button
                    $nexturl .= '/type/book/author/' . $author . '/title/' . $title;
                    $prevurl .= '/type/book/author/' . $author . '/title/' . $title;
	                break;
	                
	                
	            case 'generic':
	                $query = $this->getRequest()->getParam('q');
                    $start = (int)($page-1) * 20;

                    $dom = file_get_dom('http://images.google.com/images?q=' . urlencode($query) . '&gbv=1&um=1&hl=en&start='.$start.'&sa=N');

                    // find all image
                    $result = $dom->find('img');
                    foreach($result as $v) {
                        $start_of_big_url = strpos($v->src, 'http://', 1);
                        if($start_of_big_url){ //must be a good image to show
                            $big_img_url = substr($v->src, $start_of_big_url);
                            $modalbox_url = '/ajax/imageConfirm/item_id/'.$item_id;
                            //$modalbox_cmd = 'Modalbox.show(\''.$modalbox_url.'\', {title: \'Image Confirm\', params: {url:\'' .$big_img_url . '\'}}); return false;';
                            $modalbox_cmd = 'showImageConfirm(\'' . $big_img_url . '\', ' . $item_id . ', \'' . $search_type .'\', ' . $page .'); return false;';
                            $the_html .= '<a href="' . $v->src . '" onclick="'.$modalbox_cmd.'" />';
                            $the_html .= '<img src="' . $v->src . '">';
                            $the_html .= '</a>';
                        }
                    }
                    
                    //url for next button
                    $nexturl .= '/type/generic/q/' . $query;
                    $prevurl .= '/type/generic/q/' . $query;
	                break;
	                
            }
            $the_html .= '</div>'; //image_search_results div
            $the_html .= '<div style="text-align: center;">';
            if($page > 1){
                $the_html .= '<input type="button" value="<< Prev" onclick="Modalbox.show(\''.$prevurl.'\', {title: \'Magic Image Search\', width: 750, height: 450});">';
                $the_html .= '&nbsp;&nbsp;&nbsp;';
            }
            if($images_found){
                $the_html .= '<input type="button" value="Next >>" onclick="Modalbox.show(\''.$nexturl.'\', {title: \'Magic Image Search\', width: 750, height: 450});">';
            } else {
                $the_html .= 'No More Images Found';
            }
            $the_html .= '</div>'; //center div for buttons
            $the_html .= '</div>'; //modalbox div
	        $this->view->html = $the_html;
	        $this->view->item_id = $item_id;
    	    
        }
        $this->render('purehtml');
    }
    function saveImageFromUrlAction(){
        require_once('phpthumb.class.php');
        
        if($img_url = $this->getRequest()->getParam('url')){
            
            
            $timestamp = time();
    		$new_name = $timestamp . '.jpg';
    		$image_width = '500'; //in pix
    		$new_path = './public/images/fundraiser/';
    		$cache_path = '/var/www/images/cache/';
            
            $phpThumb = new phpThumb();
            
            $phpThumb->resetObject();
    		// Set error handling (optional)
    		$phpThumb->config_error_die_on_error = TRUE;

    		// set data
    		$phpThumb->setSourceFilename($img_url);

    		// set parameters (see "URL Parameters" below)
    		$phpThumb->setParameter('w', $image_width);

    		// set options (see phpThumb.config.php)
    		// here you must preface each option with "config_"
    		$phpThumb->setParameter('config_output_format', 'jpeg');
    		$phpThumb->setParameter('config_cache_directory', $cache_path);

            $error = 'success';
    		if ($phpThumb->GenerateThumbnail()) {

    			if (!$phpThumb->RenderToFile($new_path . $new_name)) {
    				// do something with debug/error messages
    				$data['error'] = implode("\n\n", $phpThumb->debugmessages);
    			} else {
    			    //success
    			    $data['img'] = $new_name;
			    }
    		}else{
    			$data['error'] = implode("\n\n", $phpThumb->debugmessages);
			}
        }
        
        
        
        $this->view->html = Zend_Json::encode($data);
        $this->render('purehtml');
    }
    function imageConfirmAction(){
        if($img_url = $this->getRequest()->getParam('url')){
            $item_id = $this->getRequest()->getParam('item_id');
            
            $the_html = '';
            $the_html .= '<div id="modal_image_confirm">';
            $the_html .= '<img src="'.$img_url.'" />';
            $the_html .= '</div>';

            $this->view->html = $the_html;
    	           
        }
        $this->render('purehtml'); 
    }
	function showImageAction(){
	    require_once('phpthumb.class.php');
	    
        if($img = $this->getRequest()->getParam('img')){
            if(!$image_width = $this->getRequest()->getParam('w')){
                $image_width = '500'; //in pix
            }
            if(!$image_height = $this->getRequest()->getParam('h')){
                $image_height = '500'; //in pix
            }
    		$img_path = './public/images/fundraiser/' . $img;
    		$cache_path = '/var/www/images/cache/';
            
            $phpThumb = new phpThumb();
            
            $phpThumb->resetObject();
    		// Set error handling (optional)
    		$phpThumb->config_error_die_on_error = TRUE;

    		// set data
    		$phpThumb->setSourceFilename($img_path);
            
    		// set parameters (see "URL Parameters" below)
    		$phpThumb->setParameter('w', $image_width);
            $phpThumb->setParameter('h', $image_height);
            
    		// set options (see phpThumb.config.php)
    		// here you must preface each option with "config_"
    		$phpThumb->setParameter('config_output_format', 'jpeg');
    		$phpThumb->setParameter('config_cache_directory', $cache_path);
    		
    		if ($phpThumb->GenerateThumbnail()) {
    		    $phpThumb->OutputThumbnail();
    		}
        }
        $this->render('purehtml');
    }
    /* totally temp 
    function pooAction(){
		Zend_Loader::loadClass('Schedule');
		$model = new Schedule();
		
		//if($season_id = $model->getCurrentSeasonID()){
		if($season_id = 24){
			$event_items = $model->getAllJoinedEventsBySeason($season_id);
			//var_dump($event_items);

            $the_html = '';
			foreach($event_items as $event){


                //add alternate show if it exists
				if(isset($event['alt_show_id'])){
                    $the_html .= $event['alt_show_id'] . ' ' . $event['show_id'] . '<br>';

				} 
				
			}
			$this->view->html = $the_html;
		}
		$this->render('purehtml');
	}
	*/
	
	/*temp function to recreate blank schedule
	function mysqlBlankAction(){
	    Zend_Loader::loadClass('Schedule');
		$model = new Schedule();

		$old_shows = $model->getAllEventsBySeason(24);

        $the_html = '';
		foreach($old_shows as $ns){
		    
		    unset($ns['id']);
		    unset($ns['show_id']);
		    unset($ns['alt_show_id']);
		    $ns['season_id'] = 25;
            $model->updateEvent($ns);
            $the_html .= var_export($ns);
	    }
		$this->view->html = $the_html;
		$this->render('purehtml');
    }
	*/
	/*temp function to add shows to new season
	function mysqlShowMoveAction(){
	    Zend_Loader::loadClass('Schedule');
		$model = new Schedule();

		$old_shows = $model->getAllShowsBySeason(24);

        $the_html = '';
		foreach($old_shows as $ns){
		    $ns['past_show_id'] = $ns['id'];
		    unset($ns['id']);
		    $ns['season_id'] = 25;
            $model->updateShow($ns);
            $the_html .= var_export($ns);
	    }
		$this->view->html = $the_html;
		$this->render('purehtml');
    }*/
	
	function mysqlShowMoveAction(){
	    Zend_Loader::loadClass('Schedule');
		$model = new Schedule();

		$old_shows = $model->getAllShowsBySeason(24);

        $the_html = '';
		foreach($old_shows as $ns){
		    $ns['past_show_id'] = $ns['id'];
		    unset($ns['id']);
		    $ns['season_id'] = 25;
            $model->updateShow($ns);
            $the_html .= var_export($ns);
	    }
		$this->view->html = $the_html;
		$this->render('purehtml');
    }	
	
	/*
	function getAllTracksAction(){
		Zend_Loader::loadClass('Playlists');
		$play = new Playlists();
		if($playlist_id = $this->getRequest()->getParam('playlist_id')){
			
			$the_tracks = $play->getAllTracksByPlaylist($playlist_id);
					
			$this->view->html = Zend_Json::encode($the_tracks);
			$this->render('purehtml');
		}
	}*/
}
