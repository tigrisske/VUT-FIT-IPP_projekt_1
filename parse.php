<?php



$validator = new InputValidator($argv[1]);
echo $validator->get_line(). "\n";

class InputValidator {
	//fields
	private $input;

	//methods

	//constructor
	public function __construct($input) {
		$this->input = $input;
	}
    //komentar

	public function get_line(){
		return $this->input;
	}
}


