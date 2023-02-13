<?php


$validator = new InputValidator($argv, $argc);
$lines = $validator->validate_input();
print_r($lines);

class InputValidator {
    //fields
    private  $argc;
    private $argv;

	//methods

	//constructor
	public function __construct($argv, $argc) {
       $this->argc = $argc;
       $this->argv = $argv;
    }


    //getters
	public function get_line(){
        $line = fgets(STDIN);
        return  $line;
	}

    //others

    private function display_help(){
        echo "this is help hehe \n";
    }

    public function check_args()
    {
        //too many arguments
        if ($this->argc > 2) {
            echo "vela args ty coco \n";
            exit(10);
        }

        //wrong argument
        if ($this->argc == 2) {
            if ($this->argv[1] != "--help")
                exit(10);
            else {
                $this->display_help();
                exit(0);
            }
        }
    }

    public function delete_comments(): array{
        $lines = array();
        while (($line = $this->get_line()) != false){
            $line = $this->delete_comment(rtrim($line));
            if ($line !=  '') {
                array_push($lines, $line);
            }
        }
        return $lines;
    }

    private function delete_comment(string $line){
        $line = preg_replace('/#.*$/m', '', $line);
        return $line;
    }

    public function handle_prolog(array $lines) : array{
        $line = rtrim($lines[0]);
        if (strtolower($line) != ".ippcode23") {
            echo "chyba hlavicka brasko\n";
            exit(21);
        }
        unset($lines[0]);
        return $lines;
    }

    public function validate_input() : array{
        $this->check_args();
        $lines = $this->delete_comments();
        $lines = $this->handle_prolog($lines);
        return $lines;
    }

}


