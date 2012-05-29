<HTML>
    <HEAD>
        <TITLE>moss cache</TITLE>
    </HEAD>
    <FRAMESET ROWS="150,*"><FRAMESET COLS="1000,*">
        <FRAME SRC="diff_top.php?id=<?php echo $_GET['id'];?>" NAME="top" FRAMEBORDER=0>
    </FRAMESET>
    <FRAMESET COLS="50%,50%">
        <FRAME SRC="diff_0.php?id=<?php echo $_GET['id'];?>" NAME="0">
        <FRAME SRC="diff_1.php?id=<?php echo $_GET['id'];?>" NAME="1">
    </FRAMESET>
</HTML>