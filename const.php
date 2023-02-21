<?php
const VARR = 0;
const SYMB = 1;
const TYPE = 2;
const LABEL = 3;

const FUNCTIONS = ['check_variable', 'check_symbol', 'check_type', 'check_label'];
const TYPES = ['int', 'bool', 'string'];

const FRAME = ["G", "T", "L"];
const INSTRUCTIONS = [
    "MOVE"    => [VARR, SYMB],
    "CREATEFRAME" => [],
    "PUSHFRAME"   => [],
    "POPFRAME"    => [],
    "DEFVAR"      => [VARR],
    "CALL"        => [LABEL],
    "RETURN"      => [],
    "PUSHS"       => [SYMB],
    "POPS"        => [VARR],
    "ADD"         => [VARR, SYMB, SYMB],
    "SUB"         => [VARR, SYMB, SYMB],
    "MUL"         => [VARR, SYMB, SYMB],
    "IDIV"        => [VARR, SYMB, SYMB],
    "LT"          => [VARR, SYMB, SYMB],
    "GT"          => [VARR, SYMB, SYMB],
    "EQ"          => [VARR, SYMB, SYMB],
    "AND"         => [VARR, SYMB, SYMB],
    "OR"          => [VARR, SYMB, SYMB],
    "NOT"         => [VARR, SYMB],
    "INT2CHAR"    => [VARR, SYMB],
    "STRI2INT"    => [VARR, SYMB, SYMB],
    "READ"        => [VARR, TYPE],
    "WRITE"       => [SYMB],
    "CONCAT"      => [VARR, SYMB, SYMB],
    "STRLEN"      => [VARR, SYMB],
    "GETCHAR"     => [VARR, SYMB, SYMB],
    "SETCHAR"     => [VARR, SYMB, SYMB],
    "TYPE"        => [VARR, SYMB],
    "LABEL"       => [LABEL],
    "JUMP"        => [LABEL],
    "JUMPIFEQ"    => [LABEL, SYMB, SYMB],
    "JUMPIFNEQ"   => [LABEL, SYMB, SYMB],
    "EXIT"        => [SYMB],
    "DPRINT"      => [SYMB],
    "BREAK"       => [],
];