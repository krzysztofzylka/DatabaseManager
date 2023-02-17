<?php

namespace krzysztofzylka\DatabaseManager;

class Debug {

    private static array $sql = [];

    /**
     * Show debug modal
     * @return void
     */
    public static function showDebugModal() : void {
        if (!DatabaseManager::$connection->isDebug()) {
            return;
        }

        $table = '<table class="table table-sm">
            <tr><th>#</th><th>SQL</th></tr>';

        foreach (array_reverse(Debug::getSql()) as $id => $sql) {
            $table .= '<tr><td>' . $id . '</td><td>' . nl2br($sql) . '</td></tr>';
        }

        $table .= '</table>';

        echo '
        <button class="position-fixed float-right btn btn-secondary btn-sm" style="right: 5px; bottom: 5px;" data-bs-toggle="modal" data-bs-target="#dbdebugmodal">DB Debug</button>
            <div class="modal fade" id="dbdebugmodal" tabindex="-1" aria-labelledby="dbdebugmodallabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullscreen-lg-down">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="dbdebugmodallabel">Database debug</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ' . $table . '
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zamknij</button>
                        </div>
                </div>
            </div>
        </div>
        ';
    }

    /**
     * Add SQL to debug
     * @param string $sql
     * @return void
     */
    public static function addSql(string $sql) {
        self::$sql[] = $sql;
    }

    /**
     * Get SQL list
     * @return array
     */
    public static function getSql() : array {
        return self::$sql;
    }

}