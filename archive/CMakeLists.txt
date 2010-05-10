
# Minimum version (2.6 for no reason, might be lower but i don't care)
cmake_minimum_required(VERSION 2.6)

# Project
project(StyleChecker NONE)

# Add lexer
configure_file( ${CMAKE_CURRENT_SOURCE_DIR}/lexer.py ${CMAKE_CURRENT_BINARY_DIR}/lexer.py )
configure_file( ${CMAKE_CURRENT_SOURCE_DIR}/tests/php/lexer/number.php ${CMAKE_CURRENT_BINARY_DIR}/tests/php/lexer/number.php )

# TODO add 'lexer.py' variable

# Enable CTest
enable_testing()

# Add tests:
add_test( lexer-php ./lexer.py tests/php/lexer/number.php )
