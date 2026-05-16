export function hasPermission(userPermissions: string[], permission: string) {
    return userPermissions.includes(permission);
}

export function hasRole(userRoles: string[], role: string) {
    return userRoles.includes(role);
}
