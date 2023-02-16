<?php
include 'const.php';



//const DEBUG = true;
const DEBUG = false;
$validator = new InputValidator($argv, $argc);
$lines = $validator->validate_input();
//print_r($lines);
$analyzer = new Analyzer($lines);
$analyzer->analyze();
exit(0);
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

    //other


    private function display_help(){
        echo "this is help hehe \n";
    }

    public function check_args()
    {
        //too many arguments
        if ($this->argc > 2) {
            if (DEBUG) {
                echo "Debug: Line " . __LINE__ . "\n";
            }
            exit(10);
        }

        //wrong argument
        if ($this->argc == 2) {
            if ($this->argv[1] != "--help") {
                if (DEBUG) {
                    echo "Debug: Line " . __LINE__ . "\n";
                }
                exit(10);
            } else {
                $this->display_help();
                exit(0);
            }
        }
    }

    public function delete_comments(): array{
        $lines = array();
        while (($line = $this->get_line()) != false){
            $line = $this->delete_comment($line);
            $line = ltrim(rtrim($line));
            $line = preg_replace('/\s+/', ' ', $line);
            if ($line !=  '') {
                array_push($lines, $line);
            }

        }
        //echo "lines: /n";
        //print_r($lines);
        foreach($lines as $line){
            $formated_lines[] = explode(" ", $line);
        }

        return $formated_lines;
    }

    private function delete_comment(string $line){
        $line = preg_replace('/#.*$/m', '', $line);
        return $line;
    }

    public function handle_prolog(array $lines) : array{
        if (strtolower($lines[0][0]) != ".ippcode23") {
            #echo "chyba hlavicka brasko\n";
            if (DEBUG) {
                echo "Debug: Line " . __LINE__ . "\n";
            }
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

class Analyzer
{
    private $constants = CONSTANTS;
    private $functions = FUNCTIONS;
    private $input;
    private $instructions = INSTRUCTIONS;
    private array $var_functions;

    public function __construct($input)
    {
        $this->input = $input;
        $this->var_functions = array(
            'check_variable' => function ($word) {
                //TODO - este ostatne atributy premennej skontrolovat
                return $this->check_frame($word);
            },

            'check_constant' => function($word){
                return $this->check_frame($word) or $this->check_prefix_const($word);
            },

            'check_2' => function($word){
                return true;
            },

            'check_3' => function($word){
                return true;
            }
        );
    }

    public function analyze()
    {
        foreach ($this->input as $line) {
            $instruction = $line[0];
            $args = count($line) - 1;

            if (!array_key_exists($instruction, $this->instructions)) {
                if (DEBUG) {
                    echo "Debug line:" . __LINE__ . "\n";
                }
                exit(23);
            }

            $expected_args = count($this->instructions[$instruction]);
            if ($args != $expected_args) {
                if (DEBUG) {
                    echo "Debug line:" . __LINE__ . "\n";
                }
                exit(23);
            }
            $this->check_instruction_args($line);

        }

    }
    function check_prefix_const(string $string): bool
    {
        $pattern = '/^(bool|int|nil|string)@/';
        return preg_match($pattern, $string) === 1;
    }
    private function check_const($string) : bool {
        //$constants = CONSTANTS;
        return ($this->check_prefix_const($string));
    }
    private function check_frame($string): bool
    {
        $frame = FRAME;
        if (strlen($string) <= 3) {
            if (DEBUG) {
                echo "Debug line: " . __LINE__;
            }
            return false;
        }
        if ($string[2] == '@') {
            if ($string[1] == 'F') {
                if (in_array($string[0], $frame)) {
                    return true;
                }
            }
        }

        return false;
    }



    private function check_instruction_args(array $line): bool
    {
        for ($i = 0; $i < count($line) - 1; $i++) {
            //foreach ($line as $word) {
            $word = $line[$i + 1];
            $function_index = $this->functions[$this->instructions[$line[0]][$i]];
//            echo "$line[0] \n";
//            echo "word: " . $word . "\n";
//            echo "index: " . $function_index . "\n";
//            echo "\n";
//            $this->var_functions[$function_index]($word);
//            $this->var_functions[$function_index]($word);
            if (!$this->var_functions[$function_index]($word)) {
                if (DEBUG) echo "Debug line: " . __LINE__;
                exit(23);
            }
        }
        return true;
    }
}

