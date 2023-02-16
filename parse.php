<?php
include 'const.php';

//const DEBUG = true;
const DEBUG = false;
$validator = new InputValidator($argv, $argc);
$lines = $validator->validate_input();
//print_r($lines);
$analyzer = new Analyzer($lines);
$analyzer->analyze();
if (DEBUG) echo "OK \n";
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
    private $types = TYPES;
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
                return ($this->check_frame($word) and ($this->check_var($word)));
            },

            'check_constant' => function($word){
                return $this->check_frame($word) or $this->check_const($word);
            },

            'check_type' => function($word){
                return in_array($word, $this->types);
            },

            'check_label' => function($word){
                return (preg_match( '/^[a-zA-Z0-9:_$&%*!?\-]+$/', $word) and
                    preg_match( '/^[a-zA-Z:_\$&%\*!\?][a-zA-Z:_\$&%\*!\?-]*$/',$word[0]));
            }
        );
    }

    public function analyze()
    {
        foreach ($this->input as $line) {
            $instruction = strtoupper($line[0]);
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
    private function check_const(string $string): bool
    {
        //first check if the prefix is matched
        $pattern = '/^(bool|int|nil|string)@/';
        if(!preg_match($pattern, $string)) return false;
        //split string into 2 and check what comes after "@"
        $after_at = explode('@',$string)[1];
        $before_at = explode('@',$string)[0];
        //now check whether string after "@" matches corresponding suffix
        if ($before_at == "bool") return preg_match( '/^(true|false)/', $after_at);
        if ($before_at == "nil") return preg_match( '/^(nil)/', $after_at);
        if($before_at == "int") return preg_match( '/^\d+$/',$after_at);

        //TODO dokoncit to pre string
        return true;
    }
    private function check_frame($string): bool
    {
        if (strlen($string) <= 3) {
            if (DEBUG) echo "Debug line: " . __LINE__;
            return false;
        }
        return (preg_match('/^[GTL]F@/', $string));
    }

    private function check_var($string) : bool {
        //after "@" should come alpha/special char followed by alpha/numeric/special
        //$before_at = explode("@", $string)[0];
        $after_at = explode("@", $string)[1];
        return (preg_match( '/^[a-zA-Z0-9:_$&%*!?\-]+$/', $after_at) and
            preg_match( '/^[a-zA-Z:_\$&%\*!\?][a-zA-Z:_\$&%\*!\?-]*$/',$after_at[0]));

    }
    private function check_instruction_args(array $line)
    {
        for ($i = 0; $i < count($line) - 1; $i++) {
            $word = $line[$i + 1];
            $function_index = $this->functions[$this->instructions[strtoupper($line[0])][$i]];
            if (!$this->var_functions[$function_index]($word)) {
                if (DEBUG) echo "Debug line: " . __LINE__;
                exit(23);
            }
        }
    }
}

class Generator {

}
