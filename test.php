<?php


$i = 0;
while ($i++ < 5) {
	echo "Снаружи<br />\n";
	while (1) {
		echo "В середине<br />\n";
		while (1) {
			echo "Внутри<br />\n";
			continue 3;
		}
		echo "Это никогда не будет выведено.<br />\n";
	}
	echo "Это тоже.<br />\n";
}