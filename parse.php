<?php
include 'const.php';

//const DEBUG = true;
const DEBUG = false;
$validator = new InputValidator($argv, $argc);
$lines = $validator->validate_input();
$analyzer = new Analyzer($lines);
$analyzer->analyze();
$generator = new IPPCode23($lines);
$code = $generator->toXml();
echo $code;
if (DEBUG) echo "OK \n";
exit(0);
class InputValidator {
    //fields
    private  $argc;
    private $MAXARGS=2;
    private $argv;
    private $comment_syntax_regex = '/#.*$/m';
    private $header = ".ippcode23";

	//constructor
	public function __construct($argv, $argc) {
       $this->argc = $argc;
       $this->argv = $argv;
    }


    //getters
	public function get_line() {
        return  fgets(STDIN);
	}

    //other
    private function display_help(){
        //TODO dokonci help brasko
        echo "this is help hehe \n";
    }

    public function check_args()
    {
        //too many arguments
        if ($this->argc > $this->MAXARGS) {
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
        //first we delete comments from lines
        $lines = array();
        while (($line = $this->get_line()) != false){
            $line = $this->delete_comment($line);
            $line = ltrim(rtrim($line));
            $line = preg_replace('/\s+/', ' ', $line);
            if ($line !=  '') {
                array_push($lines, $line);
            }

        }
        //next we format output into 2D array
        foreach($lines as $line){
            $formated_lines[] = explode(" ", $line);
        }

        return $formated_lines;
    }

    private function delete_comment(string $line){
        $line = preg_replace($this->comment_syntax_regex, '', $line);
        return $line;
    }

    public function handle_prolog(array $lines) : array{
        if (strtolower($lines[0][0]) != $this->header) {
            #echo "chyba hlavicka brasko\n";
            if (DEBUG) {
                echo "Debug: Line " . __LINE__ . "\n";
            }
            exit(21);
        }

        unset($lines[0]); //after validating header, we delete it
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
                return ($this->check_frame($word) and ($this->check_var($word)));
            },

            'check_symbol' => function($word){
                return $this->var_functions['check_variable']($word) or $this->check_const($word);
            },

            'check_type' => function($word){
                return in_array($word, $this->types);
            },

            'check_label' => function($word){
                return (preg_match( '/^[a-zA-Z0-9:_$&%*!?\-]+$/', $word) and
                    preg_match( '/^[a-zA-Z:_\$&%\*!\?-][a-zA-Z:_\$&%\*!\?-]*$/',$word[0]));
            }
        );
    }


    public function analyze()
    {
        foreach ($this->input as $line) {
            // for each line we check:
            $instruction = strtoupper($line[0]);
            $args = count($line) - 1;

            //1. whether the instruction exists
            if (!array_key_exists($instruction, $this->instructions)) exit(22);

            //2. whether the amount of arguments is as expected
            $expected_args = count($this->instructions[$instruction]);
            if ($args != $expected_args) exit(23);

            //whether the format of arguments is valid
            if(!$this->check_instruction_args($line)) exit(23);
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
        if ($before_at == "bool") return preg_match( '/^(true|false)$/', $after_at);
        if ($before_at == "nil") return ("nil" == $after_at);
        if($before_at == "int") return preg_match( '/^[-+]?[0-9]+$/',$after_at);
        if($before_at == "string") {
            $bool =  preg_match( '/^(?:[^\x00-\x20\x23\x5C]|\\\\0{0,2}[0-9]{1,3})*$/m',$after_at);
            //echo $after_at . "\n";
            //echo $bool . "\n";
            return $bool;
        }

        //TODO dokoncit to pre string
        return true;
    }
    private function check_frame($string): bool
    {
        return (preg_match('/^[GTL]F@/', $string));
    }

    private function check_var($string) : bool {
        //after "@" should come alpha/special char followed by alpha/numeric/special
        $after_at = explode("@", $string)[1]; //we split the string into 2 parts
        return (preg_match( '/^[a-zA-Z0-9:_$&%*!?\-]+$/', $after_at)
            and
            preg_match( '/^[a-zA-Z:_\$&%\*!\?][a-zA-Z:_\$&%\*!\?-]*$/',$after_at[0]));

    }
    private function check_instruction_args(array $line)
    {
        for ($i = 0; $i < count($line) - 1; $i++) {
            $word = $line[$i + 1];
            $function_index = $this->functions[$this->instructions[strtoupper($line[0])][$i]];
            if (!$this->var_functions[$function_index]($word)) {
                if (DEBUG) echo "Debug line: " . __LINE__;
                return false;
            }
        }
        return true;
    }
}
class IPPCode23 {
    private $lines;

    //regexes
    private $variable = '/^[GTL]F@[a-zA-Z:_$&%*!?][a-zA-Z:_$&%*!?\d*]*$/';

    public function __construct(array $lines) {
        $this->lines = $lines;
    }

    public function toXml(): string {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><program language="IPPcode23"></program>');
        $nl = 1;
        foreach ($this->lines as $line) {
            $instruction = $xml->addChild('instruction');
            $instruction->addAttribute('order', $nl);
            $instruction->addAttribute('opcode', strtoupper($line[0]));
            $nl++;
            foreach (array_slice($line, 1) as $arg) {
                $argElem = $instruction->addChild('arg' . count($instruction->children())+1);
                if ($arg == 'nil@nil') {
                    $argElem->addAttribute('type', 'nil');
                    $arg = explode("@" , $arg)[1];
                    $argElem[0] = $arg;
                } elseif (preg_match('/^int@[-+]?[0-9]+$/',$arg)) {
                    $argElem->addAttribute('type', 'int');
                    $arg = explode("@" , $arg)[1];
                    $argElem[0] = $arg;

                } elseif (preg_match('/^bool@(true|false)$/', $arg)) {
                    $argElem->addAttribute('type', 'bool');
                    $arg = explode("@" , $arg)[1];
                    $argElem[0] = $arg;


                } elseif (preg_match('/^string@(.*)$/', $arg, $matches)) {
                    $argElem->addAttribute('type', 'string');
                    $arg = explode("@" , $arg)[1];
                    $argElem[0] = $arg;
                }
                elseif (preg_match($this->variable, $arg, $matches)) {
                    //echo $arg;
                    $argElem->addAttribute('type', 'var' );
                    //$arg = explode("@" , $arg)[0];
                    $argElem[0] = $arg;
                }
                else{
                    $argElem->addAttribute('type', 'label');
                    //echo $arg;
                    //$arg = explode("@" , $arg)[1];
                    $argElem[0] = $arg;
                }
            }
        }
        //return($xml->asXML());
        $almost_final = str_replace("\n\n","\n",str_replace('><', ">\n<", $xml->asXML()));
        $search_regex = '/(var|nil|string|label|bool) \"\n/';
        $final = str_replace($search_regex, "var\">", $almost_final);
        return $almost_final;

    }
}
