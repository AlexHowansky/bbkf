# Boo Boo Kitty Fuck

Boo Boo Kitty Fuck is a feline variant of Brainfuck.

## Source Code

A Boo Boo Kitty Fuck program is a single JPEG or PNG file which contains a gallery of cat images laid out in a rectangular grid. The image grid is processed starting in the upper left corner, proceeding left-to-right and top-to-bottom. The images represent Brainfuck operators as follows:

Brainfuck Operator | Boo Boo Kitty Fuck Operator | Meaning
:-: | :-: | ---
__>__ | ![right](images/right.jpg) | Increment the data pointer.
__<__ | ![left](images/left.jpg) | Decrement the data pointer.
__+__ | ![increment](images/increment.jpg) | Increment the byte at the data pointer.
__-__ | ![decrement](images/decrement.jpg) | Decrement the byte at the data pointer.
__.__ | ![output](images/output.jpg) | Output the byte at the data pointer.
__,__ | ![input](images/input.jpg) | Accept one byte of input, storing its value in the byte at the data pointer.
__[__ | ![open](images/open.jpg) | If the byte at the data pointer is zero, then instead of moving the instruction pointer forward to the next command, jump it forward to the command after the matching ] command.
__]__ | ![close](images/close.jpg) | If the byte at the data pointer is nonzero, then instead of moving the instruction pointer forward to the next command, jump it back to the command after the matching [ command.

## Usage

The bin directory contains the following tools:

* __bbkf__ - Compile and run a Boo Boo Kitty Fuck program.
* __bbkf2bf__ - Compile a Boo Boo Kitty Fuck program to Brainfuck.
* __bf2bbkf__ - Compile a Brainfuck program to Boo Boo Kitty Fuck.
