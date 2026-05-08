<?php
/**
 * Mock $wpdb pour les tests WS Scheduler.
 * Simule les appels BDD sans connexion MySQL réelle.
 */
class MockWpdb {
    public $prefix      = 'wp_';
    public $insert_id   = 1;
    public $query_log   = [];
    public $last_table  = '';
    public $last_data   = [];
    public $last_where  = [];
    public $last_sql    = '';
    public $return_rows = [];   // injectez des rows de retour dans les tests
    public $return_var  = 5;    // valeur retournée par get_var

    public function insert( $table, $data, $format = null ) {
        $this->last_table = $table;
        $this->last_data  = $data;
        $this->query_log[] = [ 'method' => 'insert', 'table' => $table, 'data' => $data ];
        return 1;
    }

    public function get_row( $sql = null, $output = 'OBJECT', $y = 0 ) {
        $this->last_sql    = $sql;
        $this->query_log[] = [ 'method' => 'get_row', 'sql' => $sql ];
        return ! empty( $this->return_rows ) ? (object) $this->return_rows[0] : null;
    }

    public function get_results( $sql = null, $output = 'OBJECT' ) {
        $this->last_sql    = $sql;
        $this->query_log[] = [ 'method' => 'get_results', 'sql' => $sql ];
        return array_map( fn($r) => (object) $r, $this->return_rows );
    }

    public function prepare( $query, ...$args ) {
        // Flatten si tableau passé en seul arg
        if ( count( $args ) === 1 && is_array( $args[0] ) ) {
            $args = $args[0];
        }
        $this->last_sql = $query;
        return $query; // suffisant pour les assertions de test
    }

    public function update( $table, $data, $where, $format = null, $where_format = null ) {
        $this->last_table  = $table;
        $this->last_data   = $data;
        $this->last_where  = $where;
        $this->query_log[] = [ 'method' => 'update', 'table' => $table ];
        return 1;
    }

    public function delete( $table, $where, $where_format = null ) {
        $this->last_table  = $table;
        $this->last_where  = $where;
        $this->query_log[] = [ 'method' => 'delete', 'table' => $table ];
        return 1;
    }

    public function query( $sql ) {
        $this->last_sql    = $sql;
        $this->query_log[] = [ 'method' => 'query', 'sql' => $sql ];
        return 1;
    }

    public function get_var( $sql = null, $x = 0, $y = 0 ) {
        $this->last_sql    = $sql;
        $this->query_log[] = [ 'method' => 'get_var', 'sql' => $sql ];
        return $this->return_var;
    }

    public function get_col( $sql = null, $x = 0 ) {
        $this->last_sql    = $sql;
        $this->query_log[] = [ 'method' => 'get_col', 'sql' => $sql ];
        // return_rows peut contenir soit des arrays (lignes), soit des scalars (colonne)
        $out = [];
        foreach ( (array) $this->return_rows as $r ) {
            if ( is_array( $r ) ) {
                $out[] = reset( $r );
            } else {
                $out[] = $r;
            }
        }
        return $out;
    }

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    /** Réinitialise le log entre les tests. */
    public function reset() {
        $this->query_log  = [];
        $this->return_rows = [];
        $this->return_var  = 5;
    }

    /** Assertion helper : nombre d'appels à une méthode. */
    public function count_calls( $method ) {
        return count( array_filter( $this->query_log, fn($e) => $e['method'] === $method ) );
    }
}
