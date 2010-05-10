#!/usr/bin/env python
# -----------------------------------------------------------------------------
# File: $file: kwstyle.py$
# Author: $author: adrien.bailly$
# Date: $date: 3 dec 2009$
# Revision: $revision: 1$
# Description: PHP coding style validator 
# -----------------------------------------------------------------------------

import sys
sys.path.insert(0,"../..")

if sys.version_info[0] >= 3:
    raw_input = input

import ply.lex as lex
import ply.yacc as yacc
import os

from parser import parser

f = open("../../tests/php/parser/test.php", 'r')
result = parser.parse(f.read())
print result
