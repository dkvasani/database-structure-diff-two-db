<?php

class DatabaseCompare
{

    var $mysqlobject = "";
    var $linkRemote = "";
    var $DatabaseOne = "";
    var $DatabaseTwo = "";
    var $displayMatches = false;

    function SelectDatabaseOne($db)
    {
        $this->DatabaseOne = $db;
    }

    function SelectDatabaseTwo($dbb)
    {
        $this->DatabaseTwo = $dbb;
    }

    function ConnectDatabaseOne($database = "localhost", $userdb = "root", $passworddb = "", $port = 3306)
    {
        try {
            $this->mysqlobject = mysqli_connect($database, $userdb, $passworddb, $this->DatabaseOne, $port) or die(mysqli_error());
        } catch (\Exception $ex) {
            echo $ex->getMesssage();
        }
    }

    function ConnectDatabaseTwo($database = "localhost", $userdb = "root", $passworddb = "", $port = 3306)
    {
        try {
            $this->linkRemote = mysqli_connect($database, $userdb, $passworddb, $this->DatabaseTwo, $port) or die(mysqli_error());
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }

    function switchDatabaseOne($obj)
    {
        mysqli_select_db($obj, $this->DatabaseOne) or die(mysqli_error());
    }

    function switchDatabaseTwo($obj)
    {
        mysqli_select_db($obj, $this->DatabaseTwo) or die(mysqli_error());
    }

    function DoComparison()
    {
        $tableList = array();
        $tablesLocal = array();
        $tablesRemote = array();
        $dboneSQL = "SHOW TABLES FROM {$this->DatabaseOne}";
        $resultTablesMySQLOne = mysqli_query($this->mysqlobject, $dboneSQL);
        if (!$resultTablesMySQLOne) {
            echo mysqli_error($this->mysqlobject);
            exit;
        }
        $nMySql = 0;
        while ($rowTablesMySQLOne = mysqli_fetch_row($resultTablesMySQLOne)) {
            $tablesLocal[$rowTablesMySQLOne[0]] = $rowTablesMySQLOne[0];
            $nMySql++;
        }

        $dbTwoSQL = "SHOW TABLES FROM {$this->DatabaseTwo}";
        $resultTablesMySQLTwo = mysqli_query($this->linkRemote, $dbTwoSQL);
        if (!$resultTablesMySQLTwo) {
            echo mysqli_error($this->linkRemote);
            exit;
        }
        $nMySqlRemote = 0;
        while ($rowTablesMySQLTwo = mysqli_fetch_row($resultTablesMySQLTwo)) {
            $tablesRemote[$rowTablesMySQLTwo[0]] = $rowTablesMySQLTwo[0];
            $nMySqlRemote++;
        }

        echo "<table border='1' style='border-collapse:collapse;width:100%'>";
        echo "<tr><td colspan='2' style='background:#000;color:#FFF; text-align:center'><strong>List of Tables</strong></td></tr>";
        echo "<tr style='background:#000; color:#FFF; text-align:center'><td style='width:50%'><strong>Local [" . $this->DatabaseOne . "]</strong></td>";
        echo "<td style='width:50%'><strong>Remote [" . $this->DatabaseTwo . "]</strong></td></tr>";
        foreach ($tablesLocal as $tablename) {
            if (isset($tablesRemote[$tablename])) {
                $tableList[$tablename] = $tablename;
                if ($this->displayMatches) {
                    echo "<tr align='center' style='background:#DFFFDF'><td>" . $tablename . "</td>";
                    echo "<td>" . $tablesRemote[$tablename] . "</td></tr>";
                }
            } else {
                echo "<tr align='center' style='background:#FFDFDF'><td>" . $tablename . "</td>";
                echo "<td>&nbsp;</td></tr>";
            }
            unset($tablesLocal[$tablename]);
            unset($tablesRemote[$tablename]);
        }
        foreach ($tablesRemote as $tablename) {
            echo "<tr align='center' style='background:#FFDFDF'><td>&nbsp;</td>";
            echo "<td>" . $tablesRemote[$tablename] . "</td></tr>";
            unset($tablesLocal[$tablename]);
            unset($tablesRemote[$tablename]);
        }
        if ($nMySql != $nMySqlRemote) {
            echo "<tr><td colspan='2' bgcolor='#FFDFDF' align='center'><strong>Table number mismatch (Local: $nMySql tables <=> Remote: $nMySqlRemote tables)</strong></td></tr>";
        }
        echo "</table><br>&nbsp;";
        echo "<table border='1' align='center' cellpadding='2' width='100%'>";

        foreach ($tableList as $tablename) {
            $fieldsLocal = array();
            $fieldsRemote = array();
            $this->switchDatabaseOne($this->mysqlobject);
            $resultOne = mysqli_query($this->mysqlobject, "SHOW COLUMNS FROM " . $tablename);
            if (!$resultOne) {
                echo mysqli_error($this->mysqlobject);
                exit;
            }

            if (mysqli_num_rows($resultOne) > 0) {
                while ($rowTablesMySQLOne = mysqli_fetch_assoc($resultOne)) {
                    $fieldsLocal[] = $rowTablesMySQLOne;
                }
            }

            mysqli_free_result($resultOne);
            $this->switchDatabaseTwo($this->linkRemote);
            $resultTwo = mysqli_query($this->linkRemote, "SHOW COLUMNS FROM " . $tablename);

            if (!$resultTwo) {
                echo mysqli_error($this->linkRemote);
                exit;
            }
            if (mysqli_num_rows($resultTwo) > 0) {
                while ($rowTablesMySQLTwo = mysqli_fetch_assoc($resultTwo)) {
                    $fieldsRemote[] = $rowTablesMySQLTwo;
                }
            }

            mysqli_free_result($resultTwo);

            $localcount = count($fieldsLocal);
            $remotecount = count($fieldsRemote);

            $localFields = array();
            foreach ($fieldsLocal as $fl) {
                $localFields[] = $fl['Field'];
            }
            $remoteFields = array();
            foreach ($fieldsRemote as $fr) {
                $remoteFields[] = $fr['Field'];
            }

            if ($localcount == $remotecount) {
                if ($this->displayMatches) {
                    echo '<tr style="background:#DFFFDF;"><td>' . $tablename . '</td><td>Number of Columns on Local: ' . $localcount . '</td><td>Number of Columns on Remote: ' . $remotecount . '</td><td></tr>';
                    echo '<tr style="background:#DFFFDF;"><td>' . $tablename . '</td><td>' . print_r($localFields, true) . '</td><td><pre>' . print_r($remoteFields, true) . '</td><td></tr>';
                }
            } else {
                $localFields = $this->compareColumns($localFields, $remoteFields);
                $remoteFields = $this->compareColumns($remoteFields, $localFields);
                echo '<tr style="background:#FFDFDF;"><td rowspan="2">' . $tablename . '</td><td>Number of Columns on Local: ' . $localcount . '</td><td>Number of Columns on Remote: ' . $remotecount . '</td><td></tr>';
                echo '<tr style="background:#FFDFDF;"><td>';
                foreach ($localFields as $lfkey => $lf) {
                    echo $lf . '<br/>';
                }
                echo '</td><td>';
                foreach ($remoteFields as $rfkey => $rf) {
                    echo $rf . '<br/>';
                }
                echo '</td><td></tr>';
            }
        }
        echo "</table><br>&nbsp;";
    }

    function compareColumns($tableOne, $tableTwo)
    {
        $result = array_diff($tableOne, $tableTwo);
        foreach ($result as $key => $r) {
            $tableOne[$key] = '<b>' . $r . '</b>';
        }
        return $tableOne;
    }

}

?>
