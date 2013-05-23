<?php

    /**
     * Configurable
     *
     * Class which can be configured by an array of member values
     *
     * @author Oleynikov Nikolay <OleynikovNY@mail.ru>
     * @version 2.0
     */
    abstract class Configurable
    {

        /**
         * Constructs a configured instance of class with memeber values from `$membersValues`
         * 
         * @access public
         * @param array $membersValues
         * @return Configurable
         */
        public function __construct ( $membersValues = null )
        {

            $this->setMembersValues($membersValues);

        }

        /**
         * Sets member values according to the data from the input array
         *
         * @access public
         * @param array $membersValues
         * @return void
         */
        public function setMembersValues ( $membersValues )
        {

            if ( is_array($membersValues) )
            {

                foreach ( $membersValues as $memberName => $memberValue)
                {

                    $this->setMemberValue($memberName, $memberValue);

                }

            }

        }

        /**
         * Sets `$memberName` member of the class to `$memberValue` value
         *
         * @access public
         * @param string $memberName
         * @param type $memberValue
         * @return void
         */
        public function setMemberValue ( $memberName , $memberValue )
        {

            $this->checkMemberExists($memberName);

            $this->$memberName = $memberValue;

        }

        /**
         * Checks if the class has `$memberName` member. Throws an exception if not.
         *
         * @access public
         * @param string $memberName
         * @throws Exception
         * @return void
         */
        public function checkMemberExists ( $memberName )
        {

            if ( property_exists ( $this->getThisTypeName() , $memberName ) === FALSE )
            {

                throw new Exception("Property does not exist");

            }

        }

        /**
         * Retuns the class name
         *
         * @access private
         * @return string
         */
        private function getThisTypeName ()
        {
            
            return get_class($this);
            
        }

    }

    /**
     * pop3php_Letter
     *
     * RFC5322 compatible internet message
     *
     * @author Oleynikov Nikolay <OleynikovNY@mail.ru>
     * @version 2.0
     */
    class pop3php_Letter
    {

        /**
         * Letter id on the server
         * @var integer
         */
        public $id;

        /**
         * Raw letter data
         * @var string
         */
        public $rawData;

        /**
         * Array of letter headers.
         * @var array
         */
        public $headers;

        /**
         * Raw letter body
         * @var string
         */
        public $body;

        /**
         * Parse raw letter data and constructs a letter from it
         * 
         * @access public
         * @param string $rawLetterData
         * @return pop3php_Letter
         */
        public function __construct ( $rawLetterData )
        {

            $this->rawData = $rawLetterData;

            $this->validateRawData();

            $this->parseHeaders();

            $this->parseBody();

        }

        /**
         * Check if raw letter data is not empty
         *
         * @access private
         * @throws Exception
         */
        private function validateRawData ()
        {

            if ( ! isUnemptyString ( $this->rawData ) )
            {

                throw new Exception('Invalid letter data');

            }

        }

        /**
         * Parse letter headers from raw letter data
         *
         * @access private
         * @return void
         */
        private function parseHeaders ()
        {

            /*
             * RFC5322
             *
             * 2.1. Тело сообщения представляет собой последовательность символов, которая следует после раздела
             * заголовков и отделена от него пустой строкой (строкой, содержащей только CRLF).
             *
             * 2.2. Поля заголовков
             * Поля заголовков представляют собой строки, начинающиеся с имени поля,
             * за которым следует двоеточие (":"), содержимое поля и знак завершения строки CRLF.
             * 
             * 2.2.3. Однако для удобства и с учетом ограничения размеров строки (998/78 символов),
             * значение поля может быть разбито на несколько строк; это называется «фальцовкой» (folding).
             * Общим правило заключается в том, что данная спецификация разрешает включение последовательности
             * CRLF (новая строка) перед любыми пробельными символами.
             */

            foreach ( $this->getRawHeaders() as $rawHeader )
            {

                $header = pop3php_Letter::parseRawHeader($rawHeader);

                $this->headers[$header['name']] = $header['value'];

            }

        }

        /**
         * Parse letter body from raw letter data
         *
         * @access private
         * @return void
         */
        private function parseBody ()
        {

            $body = pregMatch("#(?<=\\r\\n\\r\\n).*(?=.\\r\\n$)#us",$this->rawData);
            $body = base64_decode($body);

            $this->body = $body;

        }

        /**
         *  Get an array of raw unparsed headers
         *
         * @access private
         * @return array
         */
        private function getRawHeaders ()
        {

            //  Cut headers section out of the message data
            $rawHeadersData = pregMatch("#(?<=\r\n).*?(?=\r\n\r\n)#us",$this->rawData);

            //  Split headers to pieces. RFC5322 - 2.2.3.
            $rawHeaders = preg_split ( "#(?<=\\r\\n(?! ))#us" , $rawHeadersData );

            return $rawHeaders;
            
        }

        /**
         * Parse raw header data.
         *
         * @static
         * @access private
         * @param string $rawHeaderData
         * @return array
         */
        private static function parseRawHeader ( $rawHeaderData )
        {

            //  Header name
            $headerName = pregMatch("#^.*?(?=:)#u",$rawHeaderData);

            //  Header value
            $headerValue = pregMatch("#(?<=:).*(?=$)#us",$rawHeaderData);
            $headerValue = preg_replace( array ( "#^ #" , "#\r\n$#" ) , "" , $headerValue);
            $headerValue = pop3php_Letter::base64Decode($headerValue);

            return  array
                    (
                        'name'  => $headerName,
                        'value' => $headerValue,
                    );

        }

        /**
         * Convert a string from Base64 to UTF-8 if needed
         *
         * @static
         * @access private
         * @param string $string
         * @return string
         */
        private static function base64Decode ( $string )
        {

            //  Base64 to utf-8
            $string = preg_replace ( array ( "#=\?utf\-8\?B\?#u" , "#\?=#u" , "#\=?=#u" ) , '' , $string , -1 , $count );

            //  Perform conversion if needed
            if ( $count > 1 )
            {

                $string = base64_decode ( $string );

            }

            return $string;

        }
        
    }

    /**
     * pop3php_FilterRule
     *
     * Letter filter rule. Filters out letters (not)including some string in some header
     *
     * @author Oleynikov Nikolay <OleynikovNY@mail.ru>
     * @version 2.0
     */
    class pop3php_FilterRule extends Configurable
    {
        
        /**
         * Letter header name wich will be checked
         * @var string
         */
        public $header;

        /**
         * Letters which _DO_NOT_ contain `$include` string in `$header` header will be filtered out
         * @var string
         */
        public $include;

        /**
         * Letters which _DO_ contain `$exclude` string in `$header` header will be filtered out
         * @var string
         */
        public $exclude;

        /**
         * Conctruct a letter filter rule and validate it
         *
         * @access public
         * @param array $membersValues
         * @return pop3php_FilterRule
         */
        public function __construct ( $membersValues = null )
        {

            parent::__construct($membersValues);
            
            $this->validate();
            
        }

        /**
         * Check if the letter `$letter` does pass this filter rule
         *
         * @access public
         * @param pop3php_Letter $letter
         * @return boolean
         */
        public function checkLetter ( pop3php_Letter $letter )
        {

            $this->validate();

            //  Checking `include`
            $includeOk =
                isUnemptyString ( $this->include )
                    ?
                strpos ( $letter->headers[$this->header] , $this->include ) !== false
                    :
                true;

            //  Checking `exclude`
            $excludeOk =
                isUnemptyString ( $this->exclude )
                    ?
                strpos ( $letter->headers[$this->header] , $this->exclude ) === false
                    :
            true;

            //  Checking both
            return ( $includeOk && $excludeOk ) ? true : false;
            
        }

        /**
         * Validate this filter rule for correctness. Throws an exception if the rule is invalid
         *
         * @access private
         * @throws Exception
         * @return void
         */
        private function validate ()
        {

            //  Filter is unconfigured either if `field` is unset or both `include` and `exclude` are
            if
            (
                ! isUnemptyString ( $this->header )
                    ||
                (
                    ! isUnemptyString ( $this->include )
                        &&
                    ! isUnemptyString ( $this->exclude )
                )
            )
                throw new Exception('Filter not configured');
            
        }

    }

    /**
     * pop3php_Filter
     *
     * Letter filter. Has some number of rules according to which filters incomming letters
     *
     * @author Oleynikov Nikolay <OleynikovNY@mail.ru>
     * @version 2.0
     */
    class pop3php_Filter extends Configurable
    {

        /**
         * Array of filtering rules
         * @var array
         */
        public $rules;

        /**
         * Check if `$letter` letter passes all filtering rules
         *
         * @access public
         * @param pop3php_Letter $letter
         * @return boolean
         */
        public function checkLetter ( pop3php_Letter $letter )
        {

            foreach ( $this->rules as $rule )
            {

                if ( $rule->checkLetter($letter) === false )
                {

                    return false;

                }

            }

            return true;

        }
        
    }

    /**
     * pop3php_Client
     *
     * POP3 client
     *
     * @author Oleynikov Nikolay <OleynikovNY@mail.ru>
     * @version 2.0
     */
    class pop3php_Client extends Configurable
    {

        /**
         * Server host
         * @var string
         */
        public $host;

        /**
         * Server port
         * @var integer
         */
        public $port = 110;

        /**
         * Connection timeout
         * @var integer
         */
        public $connectionTimeout = 10;

        /**
         * User name used while authentication
         * @var string
         */
        public $user;

        /**
         * User password used while authentication
         * @var string
         */
        public $password;

        /**
         * Server connection socket descriptor
         * @var ptr
         */
        private $socket = null;
        
        /**
         * Last server response
         * @var string
         */
        private $serverResponse = null;

        /**
         * Letter filter
         * @var pop3php_Filter
         */
        public $filter = null;

        /**
         * Destructor. Disconnect from the server if not yet done.
         * 
         * @access public
         */
        public function __destruct()
        {

            if ( $this->socket !== null )
            {

                try
                {
                    $this->cmdDisconnect();
                }

                //  Alredy disconnected
                catch(Exception $exception) { }
                
            }

        }

        /**
         * Attempt to connect to a remote server
         *
         * @access public
         * @throws Exception
         * @return void
         */
        public function cmdConnect ()
        {

            //  Invalid value of $this->host member
            if ( ! isUnemptyString($this->host))
                throw new Exception('Invalid host name');

            //  Trying to open socket connection
            $errorId = 0;
            $errorText = '';
            $socket = fsockopen( $this->host , $this->port , $errorId , $errorText , $this->connectionTimeout );

            //  Connection error
            if ($socket === false)
                throw new Exception('Server connection error; error code - `' . $errorId . '`; error message - `' . $errorText . '`' );

            //  Successfull connection
            $this->socket = $socket;

            //  Getting server response
            $this->retrieveServerResponse();
            
            //  Checking response status
            if ( ! $this->requestSucceeded() )
                throw new Exception('Server could not process request `connect`');

        }

        /**
         * Disconnect from a remote server
         * 
         * @access public
         * @return void
         */
        public function cmdDisconnect ()
        {

            $this->sendServerRequest("QUIT");
            $this->socket = null;

        }

        /**
         * Log in on a remote server
         * 
         * @access public
         * @throws Exception
         * @return void
         */
        public function cmdLogin ()
        {

            if ( ! isUnemptyString($this->user))
                throw new Exception("Invalid user name");

            $this->sendServerRequest( 'USER ' . $this->user );

        }

        /**
         * Send a user password to a remote server
         *
         * @access public
         * @throws Exception
         * @return void
         */
        public function cmdPassword ()
        {

            if ( ! isUnemptyString($this->password))
                throw new Exception("Invalid password");

            $this->sendServerRequest('PASS ' . $this->password );

        }

        /**
         * Retrieve a letter from a remote server
         *
         * @access public
         * @param integer $letterId
         * @param boolean $applyFilter
         * @throws Exception
         * @return pop3php_Letter
         */
        public function cmdRetrieve ( $letterId , $applyFilter = true )
        {

            /*
             *
             * Exception codes:
             * 1 - Invalid letter ID
             * 2 - Could not retrieve letter
             * 3 - Could not parse letter
             * 4 - Letter did not pass the filter
             * 5 - Letters filter is missing
             */

            //  Invalid letter ID
            if ( ! isNotZeroNumber ( $letterId ) )
                throw new Exception('Invalid letter ID',1);

            //  Trying to retrieve the letter
            try
            {
                $this->sendServerRequest ( 'RETR ' . $letterId , false );
            }
            catch(Exception $exception)
            {
                throw new Exception('Could not retrieve letter',2);
            }

            //  Trying to parse the letter
            try
            {
                $letter = new pop3php_Letter ( $this->serverResponse );
                $letter->id = $letterId;
            }
            catch(Exception $exception)
            {
                throw new Exception('Could not parse letter',3);
            }

            if ( $applyFilter )
            {

                if ( $this->filter === null )
                    throw new Exception('Invalid letters filter',5);

                if ( ! $this->filter->checkLetter ( $letter ) )
                    throw new Exception('Letter did not pass the filter',4);

            }

            return $letter;

        }

        /**
         * Delete a letter from a remote server
         *
         * @access public
         * @param integer $letterId
         * @throws Exception
         * @return void
         */
        public function cmdDelete ( $letterId )
        {

            if ( !isNotZeroNumber($letterId))
                throw new Exception("Invalid letter ID");

            $this->sendServerRequest('DELE ' . $letterId );

        }

        /**
         * Retrieve all letters from a remote server
         *
         * @access public
         * @param boolean $applyFilter
         * @throws Exception
         * @return array
         */
        public function getAllLetters ( $applyFilter = true )
        {

            //  Retrieving the list of all letters and parsing their IDs
            $this->sendServerRequest('LIST',false);

            $lettersId = pregMatch ( "#^[0-9]+(?= [0-9]+\\r\\n)#um" , $this->serverResponse , true );

            //  Array, containing full information about retrieved letters
            $result = array
            (
                'success'           => array(),
                'invalid_id'        => array(),
                'retrieve_error'    => array(),
                'parse_error'       => array(),
                'filtered_out'      => array(),
                'unknown_error'     => array(),
            );

            foreach ( $lettersId as $letterId )
            {

                //  Trying to get the letter from the server
                try
                {
                    $result['success'][] = $this->cmdRetrieve ( $letterId , $applyFilter );
                }

                //  Some exceptions may occur...
                catch ( Exception $exception )
                {
                    switch ( $exception->getCode() )
                    {
                        case 1: $result['invalid_id'][]         = $letterId; break;
                        case 2: $result['retrieve_error'][]     = $letterId; break;
                        case 3: $result['parse_error'][]        = $letterId; break;
                        case 4: $result['filtered_out'][]       = $letterId; break;
                        case 5: throw $exception;
                        default: $result['unknown_error'][]     = $letterId;
                    }
                }

            }

            return $result;

        }

        /**
         * Send a `$request` request to the server.
         *
         * @access public
         * @param string $request
         * @param boolean $signleLine
         * @throws Exception
         * @return void
         */
        public function sendServerRequest ( $request , $signleLine = true )
        {

            if( $this->socket === null )
                throw new Exception('No connection established with the serever yet');

            if ( ! isUnemptyString ( $request ) )
                throw new Exception('Empty request to the server');

            $send = fputs ( $this->socket , $request . "\r\n" );

            if ( $send === false )
                throw new Exception('Could not send you request');

            $this->retrieveServerResponse($signleLine);

            if ( ! $this->requestSucceeded() )
                throw new Exception('Server could not process request `' . $request . '`. Server response: `' . $this->serverResponse . '`');

        }

        /**
         * Get server serponse
         *
         * @access public
         * @return string
         * @throws Exception
         * @return string
         */
        public function getServerResponse ()
        {

            if ( ! isUnemptyString ( $this->serverResponse ) )
                throw new Exception('Invalid or empty server response');

            return $this->serverResponse;

        }

        /**
         * Retrieve server response
         *
         * @access private
         * @param boolean $signleLine
         * @throws Exception
         * @return void
         */
        private function retrieveServerResponse ( $signleLine = true )
        {

            if( $this->socket === null )
                throw new Exception("Not yet connected to server");

            $this->serverResponse = '';
            
            do
            {
                
                $response = fgets($this->socket);

                if ( ! isUnemptyString($response) )
                {
                    throw new Exception("Invalid or empty response");
                }

                $endOfResponse = preg_match ( "#^\.(?=\\r\\n$)#u" , $response);
                $errorReturned = preg_match ( "#^-ERR #u" , $response );

                $this->serverResponse .= $response;

            }
            
            while ( ! ( $endOfResponse || $errorReturned || $signleLine ) );

        }

        /**
         * Check if the last request succeedeed
         *
         * @access private
         * @throws Exception
         * @return boolean
         */
        private function requestSucceeded ()
        {

            if ( !isUnemptyString ( $this->serverResponse ) )
                throw new Exception("Invalid server response");

            return substr ( $this->serverResponse , 0 , 3 ) == '+OK';

        }

    }

    /**
     * Check if the `$object` object is a not empty string
     * 
     * @param object $object
     * @return boolean
     */
    function isUnemptyString ( $object )
    {

        return
        (
            is_string($object)
                &&
            strlen($object) > 0
        );

    }

    /**
     * Check if the `$object` object is a not zero number
     * 
     * @param object $object
     * @return boolean
     */
    function isNotZeroNumber ( $object )
    {

        return
        (
            is_numeric($object)
                &&
            $object !== 0
        );

    }

    /**
     * Conviency wrapper over preg_match.
     *
     * @param string $pattern
     * @param string $haystack
     * @param boolean $all
     * @return string/array
     * @throws Exception
     */
    function pregMatch ( $pattern , $haystack , $all = false )
    {

        if ( ! isUnemptyString ( $pattern ) )
            throw new Exception("Invalid pattern");

        if ( ! isUnemptyString ( $haystack ) )
            throw new Exception("Invalid haystack");

        $needles = array();

        $result =   $all
                        ?
                    preg_match_all ( $pattern, $haystack, $needles , PREG_PATTERN_ORDER )
                        :
                    preg_match ( $pattern , $haystack , $needles );

        if ( $result === false )
            throw new Exception("preg_match runtime error");

        if ( $result === 0 )
            throw new Exception("No matches found");

        //  Found something - returning result
        return $needles[0];
        
    }

?>
