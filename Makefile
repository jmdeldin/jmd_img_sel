MAKE = php /Users/jm/Sites/jmd_plugins/make_txp/make.php
SRC = img_sel.php
CACHE = ../../cache/jmd_img_sel.php
TXT = ../../releases/jmd_img_sel.txt

all:
	$(MAKE) $(SRC) $(CACHE) $(TXT)

clean:
	rm $(CACHE) $(TXT)

