<?php
class PoGOBS
{

    private $_dbType;
    private $_dbCOnfig;
    private $_dbObject;
    private $_pkmnJSON;
    private $_lastDBResult;

    public function __construct()
    {

    }

    public function getDBType()
    {
        return $this->_dbType;
    }

    public function getDBObject()
    {
        return $this->_dbObject;
    }

    public function setDBType($type)
    {
        if ($type == 'MySQL')
        {
            $this->_dbType = 'MySQL';
            return true;
        }

        if ($type == 'SQLite')
        {
            $this->_dbType = 'SQLite';
            return true;
        }

        throw new Exception('Databasetype not recognized', 1);
    }

    public function setJsonandParse($jsonFile)
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

    public function setDBConfig(array $config)
    {
        $defaults = array(
            'dbIP' => '',
            'dbUser' => '',
            'dbPassword' => '',
            'dbName' => ''
        );

        $config = array_merge($defaults, $config);

        $this->_dbCOnfig = $config;
    }

    public function startDBConnect()
    {
        if (!empty($this->_dbCOnfig))
        {
            if (!empty($this->_dbType))
            {
                switch ($this->_dbType) {
                    case 'MySQL':
                        $this->_dbObject = new mysqli($this->_dbCOnfig["dbIP"], $this->_dbCOnfig["dbUser"], $this->_dbCOnfig["dbPassword"], $this->_dbCOnfig["dbName"]);
                        if ($this->_dbObject->connect_error)
                        {
                            throw new Exception("Could not connect to Database ". $config["dbName"] ." with the given credentials", 1);
                        }
                        break;

                    case 'SQLite':
                        $this->_dbObject = new SQLite3($this->_dbCOnfig["dbName"]);
                        break;

                    default:
                        throw new Exception('Databsetype not recognized', 1);
                        break;
                }
            } else {
                throw new Exception('Databsetype not set', 1);
            }
        } else {
            throw new Exception("SQL Config not set", 1);
        }
    }

    public function createSelectFromJSON($selectedID = '')
    {
        if (!empty($this->_pkmnJSON))
        {
            $pkmn = $this->_pkmnJSON;
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
            throw new Exception('JSON File is not set', 1);
        }
    }

    public function getPokemonFromDB($pkmn_id, $returnType = 'JSON')
    {
        if (!isset($this->_dbObject))
        {
            throw new Exception('DB Object not initialized', 1);
        }

        $pkmn_id = (int) $pkmn_id;
        $pkmn_result = array();
        $added_pkmn = array();
        $query = 'SELECT lat, lon, normalized_timestamp, pokemon_id, spawn_id FROM sightings WHERE pokemon_id = '.$pkmn_id.' ORDER BY normalized_timestamp ASC';

        if ($this->_dbType == 'SQLite') {
            $fetchType = SQLITE3_ASSOC;
        } else {
            $fetchType = MYSQLI_ASSOC;
        }

        $pkmn_result_raw = $this->_dbObject->query($query);

        while ($row = $pkmn_result_raw->fetchArray($fetchType))
        {
            $addHash = $row['pokemon_id'].$row['lat'].$row['lon'];
            if (empty($added_pkmn[$addHash]))
            {
                $spawn_string = '';
                $added_pkmn[$addHash] = $row + array(
                                                    'spawn_times' => date('H:i:s', $row['normalized_timestamp']),
                                                    'spawn_timer_text' => $spawn_string,
                                                    'prev_spawn_time' => $row['normalized_timestamp'],
                                                    'spawn_timer_raw' => array()
                                                );
            } else {
                $spawn_date_1 = new DateTime();
                $spawn_date_1->setTimestamp($added_pkmn[$addHash]['prev_spawn_time']);
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
                        $spawn->setTimestamp(
                            strtotime(
                                date(
                                    (($now->format('j') + $iv->d) % date('t',$row['normalized_timestamp'])).
                                    '.m.Y '.
                                    (($now->format('G') + $iv->h) % 24).
                                    ':'.
                                    date('i',$row['normalized_timestamp']).
                                    ':00',
                                    time()
                                )
                            )
                        );
                        $iv = $now->diff($spawn);

                        $spawn_string .= '<br><br><strong>Nächster Spawn (derzeit fehlerhaft):</strong><br>'.$iv->d.' Tage(n)<br>'.$iv->h.' Stunde(n)<br>'.$iv->i.' Minute(n)';

                        $added_pkmn[$addHash]['spawn_timer_text'] = $spawn_string;
                        $added_pkmn[$addHash]['spawn_timer_raw'] = $spawn_timer_raw;
                        $added_pkmn[$addHash]['prev_spawn_time'] = $row['normalized_timestamp'];
                    }
                }

            }
        };

        foreach ($added_pkmn as $addHash => $data) {
            $pkmn_result[] = $data;
        }

        $this->_lastDBResult = $pkmn_result;

        if ($returnType == 'JSON')
        {
            return json_encode($pkmn_result, true);
        } else {
            return $pkmn_result;
        }
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
