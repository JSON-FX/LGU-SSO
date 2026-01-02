#prompt
I want to create a backend SSO (Single-sign on) to handle multiple frontend apps. The purpose of this is to handle multiple apps inside a local server for the company to use. This backend contains Employee/User Information that can be accessed through other applications as well. This app has no frontend and should only return APIs only. To control the core features of this backend app, a separate Admin only application will be used. For now let's only focus of the backend functionality. 

#user profile
first name
middle name (optional)
last name
suffix (optional)
initials (e.g, if name is John Cruz Doe should be "J.C.D". First letter of FirstName.MiddleName.LastName)
birthday
age (auto calculated from birthday)
civil status
province
city
barangay
residence
block number (optional)
building floor (optional)
house number (optional)
nationality

#core-features
1. SSO session and token management
2. Logout and session revocation
    a. Single Logout (SLO)
2. App Management
    a. Able to handle what application a user can access
3. User Roles 
    a. Able to assign roles for each registered applications (Guest, Standard, Administrator, Super Administrator)
4. API Documentation
    a. A comprehensive API Documentation for every backend requests. 

