import {StandardAction} from "../../Action";

const authActions = {
	register: "auth/Register",
	login: "auth/Login",
	reset: "auth/Reset"
};

export class AuthActions {
	public static register(name: string, email: string, password: string): Register {
		return {
			type: authActions.register,
			payload: {name, email, password}
		};
	}

	public static login(email: string, password: string): Login {
		return {
			type: authActions.login,
			payload: {email, password}
		};
	}

	public static reset(email: string): Reset {
		return {
			type: authActions.reset,
			payload: {email}
		};
	}
}

export type Register = StandardAction<{ name: string, email: string, password: string }>;
export type Login = StandardAction<{ email: string, password: string }>;
export type Reset = StandardAction<{ email: string }>;
