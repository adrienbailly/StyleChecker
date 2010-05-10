#!/usr/bin/env python
# -----------------------------------------------------------------------------
# File: $file: kwstyle.py$
# Author: $author: adrien.bailly$
# Date: $date: 3 dec 2009$
# Revision: $revision: 1$
# Description: PHP coding style validator 
# -----------------------------------------------------------------------------

import ply.lex as lex

# Tokens

reserved = ('ABSTRACT', 'AND', 'AS', 'BREAK', 'CASE', 'CATCH', 'CLASS', 'CONST',
      'CONTINUE', 'DEFAULT', 'DIE', 'DE', 'DOUBLEVAL', 'ECHO', 'ELSE', 'ELSEIF',
      'ENDFOR', 'ENDFOREACH', 'ENDIF', 'ENDSWITCH', 'ENDWHILE', 'EXIT',
      'EXTENDS', 'FALSE', 'FINAL', 'FLOATVAL', 'FOR', 'FOREACH', 'FUNCTION', 
      'GETTYPE', 'GLOBAL', 'IF', 'IMPLEMENTS', 'IMPORT', 'INCLUDE', 'INCLUDE_ONCE',
      'INSTANCEOF', 'INTERFACE', 'INTVAL', 'LIST', 'NAMESPACE', 'NEW', 'NULL', 
      'OR', 'PRINT', 'PRINT_R', 'PRIVATE', 'PROTECTED', 'PUBLIC', 'REQUIRE', 
      'REQUIRE_ONCE', 'RETURN', 'SELF', 'SERIALIZE', 'SETTYPE', 'STATIC', 'STRVAL',
      'SWITCH', 'THROW', 'TRUE', 'TRY', 'UNSERIALIZE', 'UNSET', 'VAR', 'VAR_DUMP', 
      'VAR_EXPORT', 'WHILE', 'XOR')
reserved_map = { }
for r in reserved:
    reserved_map[r.lower()] = r
    
types = ('ARRAY', 'BOOL', 'BOOLEAN', 'DOUBLE', 'FLOAT', 'INT', 'INTEGER', 
      'OBJECT', 'REAL', 'STRING')
types_map = { }
for t in types:
    types_map[t.lower()] = t

tokens = reserved + types + (
    # php start/stop
    'PHPSTART', 'PHPSTOP', 'COMMENT', 'NEWLINE', 'SPACE', 'TAB',
    
    # Literals (identifier, integer constant, float constant, string constant, char const)
    'ID', 'VARIABLE', 'TYPEID', 'ICONST', 'FCONST', 'SCONST', 'CCONST',

    # Operators (+,-,*,/,%,|,&,~,^,<<,>>, ||, &&, !, <, <=, >, >=, ==, !=, ===, !==)
    'PLUS', 'PERIOD', 'MINUS', 'TIMES', 'DIVIDE', 'MOD',
    'OROPP', 'ANDOPP', 'NOT', 'XOROPP', 'LSHIFT', 'RSHIFT',
    'LOR', 'LAND', 'LNOT',
    'LT', 'LE', 'GT', 'GE', 'EQSTR', 'NESTR', 'EQ', 'NE',
    
    # Assignment (=, *=, /=, %=, +=, -=, <<=, >>=, &=, ^=, |=)
    'EQUALS', 'TIMESEQUAL', 'DIVEQUAL', 'MODEQUAL', 'PLUSEQUAL', 'PERIODEQUAL', 
    'MINUSEQUAL', 'LSHIFTEQUAL','RSHIFTEQUAL', 'ANDEQUAL', 'XOREQUAL', 'OREQUAL',

    # Increment/decrement (++,--)
    'PLUSPLUS', 'MINUSMINUS',

    # Structure dereference (->)
    'ARROW',

    # Conditional operator (?)
    'CONDOP',
    
    # Delimeters ( ) [ ] { } , . ; :
    'LPAREN', 'RPAREN',
    'LBRACKET', 'RBRACKET',
    'LBRACE', 'RBRACE',
    'COMMA', 'SEMI', 'COLON',

    # Ellipsis (...)
    'ELLIPSIS',
    )

# Assignment operators
t_TIMESEQUAL       = r'\*='
t_DIVEQUAL         = r'/='
t_MODEQUAL         = r'%='
t_PLUSEQUAL        = r'\+='
t_PERIODEQUAL      = r'\.='
t_MINUSEQUAL       = r'-='
t_LSHIFTEQUAL      = r'<<='
t_RSHIFTEQUAL      = r'>>='
t_ANDEQUAL         = r'&='
t_OREQUAL          = r'\|='
t_XOREQUAL         = r'\^='
t_EQUALS           = r'='

# Operators
t_PLUS             = r'\+'
t_PERIOD           = r'\.'
t_MINUS            = r'-'
t_TIMES            = r'\*'
t_DIVIDE           = r'/'
t_MOD              = r'%'
t_OROPP            = r'\|'
t_ANDOPP           = r'&'
t_NOT              = r'~'
t_XOROPP           = r'\^'
t_LSHIFT           = r'<<'
t_RSHIFT           = r'>>'
t_LOR              = r'\|\|'
t_LAND             = r'&&'
t_LNOT             = r'!'
t_LT               = r'<'
t_GT               = r'>'
t_LE               = r'<='
t_GE               = r'>='
t_EQSTR            = r'==='
t_NESTR            = r'!=='
t_EQ               = r'=='
t_NE               = r'!='

# Increment/decrement
t_PLUSPLUS         = r'\+\+'
t_MINUSMINUS       = r'--'

# ->
t_ARROW            = r'->'

# ?
t_CONDOP           = r'\?'

# Delimeters
t_LPAREN           = r'\('
t_RPAREN           = r'\)'
t_LBRACKET         = r'\['
t_RBRACKET         = r'\]'
t_LBRACE           = r'\{'
t_RBRACE           = r'\}'
t_COMMA            = r','
t_SEMI             = r';'
t_COLON            = r':'
t_ELLIPSIS         = r'\.\.\.'

# Start/Stop PHP
t_PHPSTART         = r'<\?php'
t_PHPSTOP          = r'\?>'

# Integer literal
t_ICONST = r'\d+([uU]|[lL]|[uU][lL]|[lL][uU])?'

# Floating literal
t_FCONST = r'((\d+)(\.\d+)(e(\+|-)?(\d+))? | (\d+)e(\+|-)?(\d+))([lL]|[fF])?'

# String literal
t_SCONST = r'\"([^\\]|(\\.))*?\"'

# Character constant 'c' or L'c'
t_CCONST = r'(L)?\'([^\\\n]|(\\.))*?\''

def t_VARIABLE(t):
    r'\$[A-Za-z_][\w_]*'
    return t
        
def t_ID(t):
    r'[A-Za-z_][\w_]*'
    t.type = reserved_map.get(t.value, "ID")
    t.type = types_map.get(t.value, t.type)
    return t

def t_tab(t):
    r'\t'
    t.type = 'TAB'
    return t
    
def t_space(t):
    r'\ '
    t.type = 'SPACE'
    return t

def t_newline(t):
    r'\n'
    t.lexer.lineno += 1
    t.type = "NEWLINE"
    return t

# Comments
def t_comment(t):
    r'/\*(.|\n)*?\*/'
    t.lexer.lineno += t.value.count('\n')
    t.type = "COMMENT"
    return t
    
def t_single_line_comment(t):
    r'//.*\n'
    t.lexer.lineno += 1;
    t.type= "COMMENT"
    return t
            
def t_error(t):
    print("LEX> Error line %d: Illegal character '%s' in \"%s\"" % (t.lexer.lineo, t.value[0], t.value))
    t.lexer.skip(1)
        
lexer = lex.lex(debug=0)
if __name__ == "__main__":
    lex.runmain(lexer)



