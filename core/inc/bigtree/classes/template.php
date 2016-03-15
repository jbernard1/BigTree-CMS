<?php
	/*
		Class: BigTree\Template
			Provides an interface for handling BigTree templates.
	*/

	namespace BigTree;

	use BigTree;
	use BigTreeCMS;

	class Template extends BaseObject {

		static $Table = "bigtree_templates";

		protected $ID;
		protected $Routed;

		public $Fields;
		public $Level;
		public $Module;
		public $Name;
		public $Position;

		/*
			Constructor:
				Builds a Template object referencing an existing database entry.

			Parameters:
				template - Either an ID (to pull a record) or an array (to use the array as the record)
		*/

		function __construct($template) {
			// Passing in just an ID
			if (!is_array($template)) {
				$template = BigTreeCMS::$DB->fetch("SELECT * FROM bigtree_templates WHERE id = ?", $template);
			}

			// Bad data set
			if (!is_array($template)) {
				trigger_error("Invalid ID or data set passed to constructor.", E_WARNING);
			} else {
				$this->ID = $template["id"];

				$this->Fields = array_filter((array) @json_decode($template["resources"],true));
				$this->Level = $template["level"];
				$this->Module = $template["module"];
				$this->Name = $template["name"];
				$this->Position = $template["position"];
				$this->Routed = $template["route"] ? true : false;
			}
		}

		/*
			Function: create
				Creates a template and its default files/directories.

			Parameters:
				id - Id for the template.
				name - Name
				routed - Basic ("") or Routed ("on")
				level - Access level (0 for everyone, 1 for administrators, 2 for developers)
				module - Related module id
				fields - An array of fields

			Returns:
				Template object if successful, false if there's an id collision or a bad ID is passed
		*/

		function create($id,$name,$routed,$level,$module,$fields) {
			// Check to see if it's a valid ID
			if (!ctype_alnum(str_replace(array("-","_"),"",$id)) || strlen($id) > 127) {
				return false;
			}

			// Check to see if the id already exists
			if (BigTreeCMS::$DB->exists("bigtree_templates",$id)) {
				return false;
			}

			// If we're creating a new file, let's populate it with some convenience things to show what resources are available.
			$file_contents = "<?\n	/*\n		Fields Available:\n";

			// Grabbing field types so we can put their name in the template file
			$types = BigTree\FieldType::reference(false,"templates");

			// Loop through fields and create cleaned up versions
			foreach ($fields as $key => $field) {
				if (!$field["id"]) {
					unset($fields[$key]);
				} else {
					$field_data = array(
						"id" => BigTree::safeEncode($field["id"]),
						"type" => BigTree::safeEncode($field["type"]),
						"title" => BigTree::safeEncode($field["title"]),
						"subtitle" => BigTree::safeEncode($field["subtitle"]),
						"options" => (array)@json_decode($field["options"],true)
					);

					// Backwards compatibility with BigTree 4.1 package imports
					foreach ($field as $key => $value) {
						if (!in_array($key,array("id","title","subtitle","type","options"))) {
							$field_data["options"][$key] = $value;
						}
					}

					$fields[$key] = $field_data;

					$file_contents .= '		$'.$field["id"].' = '.$field["title"].' - '.$types[$field["type"]]["name"]."\n";
				}
			}

			$file_contents .= '	*/
?>';
			if (!count($fields)) {
				$file_contents = "";
			}

			if ($routed) {
				if (!file_exists(SERVER_ROOT."templates/routed/".$id."/default.php")) {
					BigTree::putFile(SERVER_ROOT."templates/routed/".$id."/default.php",$file_contents);
				}
			} elseif (!file_exists(SERVER_ROOT."templates/basic/".$id.".php")) {
				BigTree::putFile(SERVER_ROOT."templates/basic/".$id.".php",$file_contents);
			}

			// Increase the count of the positions on all templates by 1 so that this new template is for sure in last position.
			BigTreeCMS::$DB->query("UPDATE bigtree_templates SET position = position + 1");

			// Insert template
			BigTreeCMS::$DB->insert("bigtree_templates",array(
				"id" => $id,
				"name" => BigTree::safeEncode($name),
				"module" => $module,
				"resources" => $fields,
				"level" => $level,
				"routed" => $routed ? "on" : ""
			));

			AuditTrail::track("bigtree_templates",$id,"created");

			return new Template($id);
		}

		/*
			Function: delete
				Deletes the template and its related files.
		*/

		function delete($id) {
			// Delete related files
			if ($this->Routed) {
				BigTree::deleteDirectory(SERVER_ROOT."templates/routed/".$this->ID."/");
			} else {
				BigTree::deleteFile(SERVER_ROOT."templates/basic/".$this->ID.".php");
			}

			BigTreeCMS::$DB->delete("bigtree_templates",$this->ID);
			
			AuditTrail::track("bigtree_templates",$this->ID,"deleted");
		}

		/*
			Function: save
				Saves the current object properties back to the database.
		*/

		function save() {
			// Clean up fields
			$fields = array();
			foreach ($this->Fields as $field) {
				if ($field["id"]) {
					$fields[] = array(
						"id" => BigTree::safeEncode($field["id"]),
						"title" => BigTree::safeEncode($field["title"]),
						"subtitle" => BigTree::safeEncode($field["subtitle"]),
						"type" => BigTree::safeEncode($field["type"]),
						"options" => is_array($field["options"]) ? $field["options"] : json_decode($field["options"],true)
					);
				}
			}
			
			// Update DB
			BigTreeCMS::$DB->update("bigtree_templates",$this->ID,array(
				"name" => BigTree::safeEncode($this->Name),
				"resources" => array_filter($fields),
				"module" => $this->Module,
				"level" => $this->Level,
				"position" => $this->Position
			));

			// Track
			AuditTrail::track("bigtree_templates",$this->ID,"updated");
		}

		/*
			Function: update
				Updates the template.

			Parameters:
				name - Name
				level - Access level (0 for everyone, 1 for administrators, 2 for developers)
				module - Related module id
				fields - An array of fields
		*/

		function update($name,$level,$module,$fields) {
			$this->Fields = $fields;
			$this->Level = $level;
			$this->Module = $module;
			$this->Name = $name;

			$this->save();
		}

	}