<?php

namespace App\Utils;

class Message
{
    /**
     * Errors
     */
    public const GENERIC_KO = "[00000] - Generic Error.";
    public const VALIDATION_KO = "[00001] - Error during validation.";
    public const AUTH_KO = "[00002] - Error during Authentication.";
    public const FILTER_KO = "[00003] - Invalid filter.";
    public const RELATION_KO = "[00004] - Invalid relation.";
    public const INDEX_KO = "[00005] - Error during index.";
    public const SHOW_KO = "[00006] - Error during show.";
    public const CREATE_KO = "[00007] - Error during create.";
    public const UPDATE_KO = "[00008] - Error during update.";
    public const DELETE_KO = "[00008] - Error during delete.";
    public const NOT_FOUND = "[00009] - Not found.";
    public const YOU_CANT = "[00010] - You can't.";
    public const HISTORICIZING_KO = "[00011] - Error during batch order historicizing.";
    public const DELETE_KO_RELATIONSHIP = "[00012] - You can't delete. There are associated entities.";
    public const EMAIL_ALREADY_EXISTS_KO = "[00013] - The email you are trying to use is already in use.";

    /**
     * Messages
     */
    public const OK = "OK.";
    public const AUTH_OK = "Authentication successful.";
    public const INDEX_OK = "Index successful.";
    public const SHOW_OK = "Show successful.";
    public const CREATE_OK = "Create successful.";
    public const UPDATE_OK = "Update successful.";
    public const DELETE_OK = "Delete successful.";
    public const MAIL_OK = "Mail sent successful.";

    /**
     * Talking with user
     */
    public const CHECK_CREDENTIALS = "Check your credentials.";
    public const THAKNS = "Thanks.";
}