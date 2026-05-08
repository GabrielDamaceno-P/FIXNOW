<?php

class Conexao
{
    private static ?PDO $instancia = null;

    public static function getConexao(): PDO
    {
        if (self::$instancia === null) {
            $host   = 'localhost';
            $port   = 3306;
            $dbname = 'projeto_fixnow';
            $user   = 'root';
            $pass   = '';

            self::$instancia = new PDO(
                "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        }

        return self::$instancia;
    }
}
