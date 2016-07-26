<?php
class PoGOBS
{

    private $_conn;
    private $_dbType;
    private $_mySQLConfig;
    private $_SQLiteDB;
    private $_SQLiteDBObj;
    private $_pkmnJSON;

    public function __construct()
    {

    }

    public function getDBType()
    {
        return $this->_dbType;
    }

    public function setDBType($type)
    {
        if ($type == 'MySQL')
        {
            $this->_dbType = 'MySQL';
        } else {
            $this->_dbType = 'SQLite';
        }
    }

    public function setJsonFile($jsonFile)
    {
        if (file_exists($jsonFile))
        {
            $pkmn_data = file_get_contents($jsonFile);
            $pkmn = json_decode($pkmn_data, true);
            $this->_pkmnJSON = $pkmn;
        } else {
            throw new Exception('JSON File does not exist', 1);
        }
    }

    public function setMySQLConfig(array $config)
    {
        $defaults = array(
            'ip' => '',
            'user' => '',
            'password' => '',
            'dbname' => ''
        );

        $config = array_merge($defaults, $config);

        $this->_mySQLConfig = $config;
    }

    public function startMySQLConnect()
    {
        if (!empty($this->_mySQLConfig))
        {
            $this->_conn = new mysqli($this->_mySQLConfig["ip"], $this->_mySQLConfig["user"], $this->_mySQLConfig["password"], $this->_mySQLConfig["dbname"]);
            if ($this->_conn->connect_error)
            {
                throw new Exception("Could not connect to Database ". $config["dbname"] ." with the given credentials", 1);
            }
        } else {
            throw new Exception("SQL Config not set", 1);
        }
    }

    public function setSQLiteDB($path)
    {
        $this->_SQLiteDB = $path;
        return $this;
    }

    public function getJSONPKMN($type = 'select', $selectedID = '')
    {
        if (!empty($this->_pkmnJSON))
        {
            $pkmn = $this->_pkmnJSON;
            if ($type == 'select')
            {
                $return = '<select name="system" id="system"><option></option><optgroup label="Derzeit verfügbar">';
                foreach ($pkmn as $pkmnid => $pkmnname) {
                    $return .= '<option value="'.$pkmnid.'" '.($pkmnid==$selectedID?'selected="selected"':'').'>'.$pkmnname.'</option>';
                    if ($pkmnid == 151)
                    {
                        $return .='</optgroup><optgroup label="Noch nicht verfügbar">';
                    }
                }
                $return .= '</optgroup></select>';
                return $return;
            } else {
                return $pkmn;
            }
        } else {
            throw new Exception('JSON File is not set', 1);
        }
    }

    public function connectSQLite()
    {
        if (!isset($this->_SQLiteDB))
        {
            throw new Exception('SQliteDB not set', 1);
        } else {
            $this->_SQLiteDBObj = new SQLite3($this->_SQLiteDB);
        }
        return $this;
    }

    public function getSQLiteDB()
    {
        return $this->_SQLiteDBObj;
    }

    public function getPokemon($pkmn_id)
    {
        if (!isset($this->_dbType))
        {
            throw new Exception('DB Type not set', 1);
        }

        if ($this->_dbType == 'SQLite' && !isset($this->_SQLiteDBObj)) {
            throw new Exception('Database is SQLite but Databse Object does not exist', 1);
        }

        $pkmn_id = (int) $pkmn_id;
        $pkmn_result = array();
        $added_pkmn = array();
        if ($this->getDBType() == 'SQLite') {
            $pkmn_result_raw = $this->_SQLiteDBObj->query('SELECT lat, lon, normalized_timestamp, pokemon_id, spawn_id FROM sightings WHERE pokemon_id = '.$pkmn_id.' ORDER BY normalized_timestamp ASC');
        } else {

        }
        while ($row = $pkmn_result_raw->fetchArray(SQLITE3_ASSOC))
        {
            $addHash = $row['pokemon_id'].$row['lat'].$row['lon'];
            if (empty($added_pkmn[$addHash]))
            {
                $spawn_string = '';
                $now = new DateTime();
                $spawn = new DateTime();
                $spawn->setTimestamp(strtotime(date('d.m.Y '.(($now->format('H')+date('H',$row['normalized_timestamp']))%24).':'.date('i',$row['normalized_timestamp']).':00', time())));
                $iv = $now->diff($spawn);
                // $spawn_string .= ' (In '.$iv->d.' Tage(n) '.$iv->h.' Stunde(n) '.$iv->i.' Minute(n))';
                $added_pkmn[$addHash] = $row + array('spawn_times' => date('H:i:s', $row['normalized_timestamp']), 'spawn_timer_text' => $spawn_string, 'prev_spawn' => $row['normalized_timestamp'], 'spawn_timer_raw' => array());
            } else {
                $spawn_date_1 = new DateTime();
                $spawn_date_1->setTimestamp($added_pkmn[$addHash]['prev_spawn']);
                $spawn_date_2 = new DateTime();
                $spawn_date_2->setTimestamp($row['normalized_timestamp']);
                $iv = $spawn_date_2->diff($spawn_date_1);
                $spawn_timer_raw = array('d' => $iv->d, "h" => $iv->h, "i" => $iv->i);


                if (empty($added_pkmn[$addHash]['spawn_timer_raw']) || $spawn_timer_raw < $added_pkmn[$addHash]['spawn_timer_raw'])
                {
                    if ($spawn_date_1 == $spawn_date_2) {
                        $added_pkmn[$addHash]['spawn_times'] = date('H:i:s', $row['normalized_timestamp']);
                    } else {
                        $added_pkmn[$addHash]['spawn_times'] = '';
                        $spawn_string = '';
                        $spawn_string .= ($iv->d>0?($iv->d==1?'täglich '.($iv->d>0?'alle ':''):'alle '.$iv->d.' Tage '):'');
                        $spawn_string .= ($iv->h>0?($iv->h==1?'stündlich ':($iv->d==0?'alle ':'').$iv->h.' Stunden '):'').($iv->i==0?'um :'.date('i',$row['normalized_timestamp']):'');
                        $spawn_string .= ($iv->i>0?($iv->i==1?'minütlich':($iv->d==0 && $iv->h==0?'alle ':'').($iv->h>0?'und ':'').$iv->i.' Minuten'):'');
                        $now = new DateTime();
                        $spawn = new DateTime();
                        // $spawn->setTimestamp(strtotime(date('d.m.Y '.(($now->format('H')+date('H',$row['normalized_timestamp']))%24).':'.date('i',$row['normalized_timestamp']).':00', time())));
                        $iv = $now->diff($spawn);

                        // $spawn_string .= ' (In '.$iv->d.' Tage(n) '.$iv->h.' Stunde(n) '.$iv->i.' Minute(n))';

                        $added_pkmn[$addHash]['spawn_timer_text'] = $spawn_string;
                        // $added_pkmn[$addHash]['spawn_timer'] = '<br>Spawn alle '.$iv->d.' Tage '.$iv->h.' Stunden '.$iv->i.' Minuten <br>';
                        $added_pkmn[$addHash]['spawn_timer_raw'] = $spawn_timer_raw;
                        $added_pkmn[$addHash]['prev_spawn'] = $row['normalized_timestamp'];
                    }
                }

            }
        };
        foreach ($added_pkmn as $addHash => $data) {
            $pkmn_result[] = $data;
        }
        return json_encode($pkmn_result, true);
    }

    public function getPokemonIDbyName($pkmn_name)
    {
        if (!empty($this->_pkmnJSON))
        {
            $pkmn = $this->_pkmnJSON;
            $pkmn_name = filter_var($pkmn_name);
            $pkmn_id = array_search(strtolower($pkmn_name), array_map('strtolower',$this->_pkmnJSON));
            return $pkmn_id;
        } else {
            throw new Exception('JSON File is not set', 1);
        }
    }

    public function getPokemonNamebyID($pkmn_id)
    {
        if (!empty($this->_pkmnJSON))
        {
            $pkmn_id = (int) $pkmn_id;
            return $this->_pkmnJSON[$pkmn_id];
        } else {
            throw new Exception('JSON File is not set', 1);
        }
    }
}
