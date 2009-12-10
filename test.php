<?php
$foo = array("foo", "bar", "baz");
unset($foo[1]);
print_r(array_values($foo));
?>
