<?php
const VARIABLE = 0;
const SYMBOL = 1;
const TYPE = 2;
const LABEL = 3;

const FUNCTIONS = ['check_variable', 'check_constant', 'check_type', 'check_label'];
const TYPES = ['int', 'bool', 'string'];

const FRAME = ["G", "T", "L"];
const INSTRUCTIONS = [
    "MOVE"    => [VARIABLE, SYMBOL],
    "CREATEFRAME" => [],
    "PUSHFRAME"   => [],
    "POPFRAME"    => [],
    "DEFVAR"      => [VARIABLE],
    "CALL"        => [LABEL],
    "RETURN"      => [],
    "PUSHS"       => [SYMBOL],
    "POPS"        => [VARIABLE],
    "ADD"         => [VARIABLE, SYMBOL, SYMBOL],
    "SUB"         => [VARIABLE, SYMBOL, SYMBOL],
    "MUL"         => [VARIABLE, SYMBOL, SYMBOL],
    "IDIV"        => [VARIABLE, SYMBOL, SYMBOL],
    "LT"          => [VARIABLE, SYMBOL, SYMBOL],
    "GT"          => [VARIABLE, SYMBOL, SYMBOL],
    "EQ"          => [VARIABLE, SYMBOL, SYMBOL],
    "AND"         => [VARIABLE, SYMBOL, SYMBOL],
    "OR"          => [VARIABLE, SYMBOL, SYMBOL],
    "NOT"         => [VARIABLE, SYMBOL],
    "INT2CHAR"    => [VARIABLE, SYMBOL],
    "STRI2INT"    => [VARIABLE, SYMBOL, SYMBOL],
    "READ"        => [VARIABLE, TYPE],
    "WRITE"       => [SYMBOL],
    "CONCAT"      => [VARIABLE, SYMBOL, SYMBOL],
    "STRLEN"      => [VARIABLE, SYMBOL],
    "GETCHAR"     => [VARIABLE, SYMBOL, SYMBOL],
    "SETCHAR"     => [VARIABLE, SYMBOL, SYMBOL],
    "TYPE"        => [VARIABLE, SYMBOL],
    "LABEL"       => [LABEL],
    "JUMP"        => [LABEL],
    "JUMPIFEQ"    => [LABEL, SYMBOL, SYMBOL],
    "JUMPIFNEQ"   => [LABEL, SYMBOL, SYMBOL],
    "EXIT"        => [SYMBOL],
    "DPRINT"      => [SYMBOL],
    "BREAK"       => [],
];