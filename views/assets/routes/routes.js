console.log("Routes Instance loading...");

class Routes {
    constructor() {
        this._routes = {};
    }

    setRoutes(routes) {
        this._routes = { ...this._routes, ...routes };
    }

    get(route) {
        if (this._routes[route]) {
            return this._routes[route];
        }
        alert("Rotta non trovata: " + route);
        return false;
    }

    getAllRoutes() {
        return this._routes;
    }
}

// Singleton
const RoutesSingletonInstance = new Routes();
export default RoutesSingletonInstance;
