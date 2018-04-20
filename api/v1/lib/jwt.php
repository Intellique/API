<?php

#Claim sources
require_once("jwt/Claim.php");
require_once("jwt/Claim/Basic.php");
require_once("jwt/Claim/Validatable.php");
require_once("jwt/Claim/EqualsTo.php");
require_once("jwt/Claim/LesserOrEqualsTo.php");
require_once("jwt/Claim/GreaterOrEqualsTo.php");
require_once("jwt/Token.php");

#Builder sources
require_once("jwt/Claim/Factory.php");
require_once("jwt/Parsing/Encoder.php");
require_once("jwt/Builder.php");

#Parser sources
require_once("jwt/Parser.php");
require_once("jwt/Parsing/Decoder.php");

#Validation sources
require_once("jwt/ValidationData.php");

#Signature sources
require_once("jwt/Signature.php");
require_once("jwt/Signer.php");
require_once("jwt/Signer/Key.php");
require_once("jwt/Signer/BaseSigner.php");
require_once("jwt/Signer/Hmac.php");
require_once("jwt/Signer/Hmac/Sha256.php");

#Namespace constructs used in sources
use Lcobucci\JWT;
use Lcobucci\JWT\Claim\Factory;
use Lcobucci\JWT\Parsing;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac;

