
# Minimum version (2.6 for no reason, might be lower but i don't care)
cmake_minimum_required(VERSION 2.6)

# Add lexer
INSTALL_FILES(/bin lexer.py)
# TODO add 'lexer.py' variable

# Enable CTest
ENABLE_TESTING()

# Add tests:
ADD_TEST(SimpleTest ./lexer.py tests/php/lexer/number.php)

